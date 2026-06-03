#!/usr/bin/env python3

import argparse
import json
import requests
import sys

# Configuration (copied from the existing v3 script)
GLPI_URL = "http://localhost:82"
CLIENT_ID = "03fb26a77d9c3325df8c977dae6d4e92bdddb7c2a13ccd6652f2cdd3cbdc42f7"
CLIENT_SECRET = "bde2c6ed3c770208a83f88e56bdb6c04693d570af09673923a2d02d91ba88dda"
GLPI_USER = "glpi"
GLPI_PASS = "glpi"

ARM_BASE = "http://localhost/api/assets"
ARM_TOKEN = "xPC3jy1uQZwM6byqmaxlbOgO7UDRhBETSIfR9w86fd249713"

ASSET_TYPE_MAP = {
    "Desktop": "armdesktop",
    "Laptop": "armlaptop",
    "Workstation": "armworkstation",
    "Server": "armserver",
    "Mainframe": "armmainframe",
    "NAS": "armnas",
    "Other": "armother",
    "NVR": "armnvr",
    "DVR": "armdvr",
    "Router": "armrouter",
    "Switch": "armswitch",
    "IP Camera": "armipcamera",
    "Analog Camera": "armanalogcamera"
}


class GLPI:

    def __init__(self):
        self.base = GLPI_URL.rstrip("/") + "/api.php"
        self.session = requests.Session()
        self.token = None

    def auth(self):
        r = self.session.post(
            self.base + "/token",
            headers={"Content-Type": "application/json"},
            data=json.dumps({
                "grant_type": "password",
                "client_id": CLIENT_ID,
                "client_secret": CLIENT_SECRET,
                "username": GLPI_USER,
                "password": GLPI_PASS,
                "scope": "api"
            })
        )
        r.raise_for_status()
        self.token = r.json()["access_token"]

    def headers(self):
        return {
            "Authorization": f"Bearer {self.token}",
            "Accept": "application/json"
        }

    def headers_json(self):
        h = self.headers()
        h["Content-Type"] = "application/json"
        return h

    def endpoint(self, itemtype, custom=True):
        if custom:
            return f"{self.base}/v2/Assets/Custom/{itemtype}"
        return f"{self.base}/v2/Assets/{itemtype}"

    def _request(self, method, itemtype, custom=True, suffix=None, **kwargs):
        url = self.endpoint(itemtype, custom=custom)
        if suffix:
            url = f"{url}/{suffix}"
        r = self.session.request(method, url, **kwargs)
        if custom and r.status_code == 404:
            url = self.endpoint(itemtype, custom=False)
            if suffix:
                url = f"{url}/{suffix}"
            r = self.session.request(method, url, **kwargs)
        return r

    def find(self, itemtype, arm_id):
        r = self._request(
            "GET",
            itemtype,
            custom=True,
            headers=self.headers(),
        )
        if r.status_code in (400, 404):
            return None
        r.raise_for_status()

        items = r.json()
        items = items if isinstance(items, list) else items.get("data", [])

        for i in items:
            if str(i.get("custom_fields", {}).get("arm_id")) == str(arm_id):
                return i.get("id")

        return None

    def find_any(self, arm_id):
        for itemtype in set(ASSET_TYPE_MAP.values()):
            existing = self.find(itemtype, arm_id)
            if existing:
                return itemtype, existing
        return None, None

    def create(self, itemtype, payload):
        r = self._request(
            "POST",
            itemtype,
            custom=True,
            headers=self.headers_json(),
            json=payload,
        )
        if r.status_code >= 400:
            print("[CREATE ERROR]", r.status_code, r.text)
        r.raise_for_status()

    def update(self, itemtype, asset_id, payload):
        # Try custom path first, then fallback to standard endpoint.
        r = self._request(
            "PATCH",
            itemtype,
            custom=True,
            suffix=asset_id,
            headers=self.headers_json(),
            json=payload,
        )
        if r.status_code == 404:
            # The fallback already happened if necessary, so this means no viable endpoint.
            print("[UPDATE ERROR] endpoint not found for", itemtype)
        if r.status_code >= 400:
            print("[UPDATE ERROR]", r.status_code, r.text)
        r.raise_for_status()

    def delete(self, itemtype, asset_id):
        r = self._request(
            "DELETE",
            itemtype,
            custom=True,
            suffix=asset_id,
            headers=self.headers(),
        )
        if r.status_code == 404:
            return False
        r.raise_for_status()
        return True

    def upsert(self, itemtype, payload):
        arm_id = payload["custom_fields"]["arm_id"]

        existing_type, existing_id = self.find_any(arm_id)

        if existing_id:
            if existing_type != itemtype:
                print(f"[i] arm_id {arm_id} exists under {existing_type}; moving asset to {itemtype}")
                if self.delete(existing_type, existing_id):
                    self.create(itemtype, payload)
                else:
                    raise RuntimeError(f"Failed to delete existing asset {existing_id} from {existing_type}")
            else:
                self.update(itemtype, existing_id, payload)
        else:
            self.create(itemtype, payload)


def fetch_asset_by_id(asset_id):
    url = ARM_BASE.rstrip("/") + "/" + str(asset_id)
    r = requests.get(url, headers={"Authorization": f"Bearer {ARM_TOKEN}", "Accept": "application/json"})
    r.raise_for_status()
    data = r.json()
    # assume the API returns the asset object directly or under 'data'
    if isinstance(data, dict) and "data" in data:
        return data["data"]
    return data


def resolve_dropdown(glpi, endpoint, name):
    if not name:
        return None

    name_clean = name.strip().lower()

    r = glpi.session.get(
        glpi.base + "/v2/Dropdowns/" + endpoint,
        headers=glpi.headers()
    )
    r.raise_for_status()

    items = r.json()
    items = items if isinstance(items, list) else items.get("data", [])

    for i in items:
        if i.get("name") and i["name"].strip().lower() == name_clean:
            return i["id"]

    r = glpi.session.post(
        glpi.base + "/v2/Dropdowns/" + endpoint,
        headers=glpi.headers_json(),
        json={"name": name.strip()}
    )
    r.raise_for_status()

    return r.json()["id"]


def build_payload(glpi, asset):
    manufacturer_id = resolve_dropdown(glpi, "Manufacturer", asset.get("manufacturer"))
    location_id = resolve_dropdown(glpi, "Location", asset.get("location"))

    return {
        "name": asset.get("asset_name") or asset.get("name"),
        "serial": asset.get("sku"),
        "comment": asset.get("description"),

        "manufacturer": manufacturer_id,
        "location": location_id,

        "custom_fields": {
            "arm_id": asset.get("asset_id") or asset.get("id"),
            "version": asset.get("version"),
            "ip_address": asset.get("ip_address"),
            "mac_address": asset.get("mac_address"),
            "fqdn": asset.get("fqdn"),
            "total_appreciation": asset.get("total_appreciation")
        }
    }


def main():
    p = argparse.ArgumentParser(description="Sync a single ARM asset to GLPI")
    p.add_argument("--id", required=True, help="ARM asset id to fetch and sync")
    args = p.parse_args()

    asset_id = args.id

    try:
        asset = fetch_asset_by_id(asset_id)
    except Exception as e:
        print("Failed to fetch asset:", e)
        sys.exit(1)

    if not asset:
        print("No asset data returned for id", asset_id)
        sys.exit(1)

    itemtype = ASSET_TYPE_MAP.get(asset.get("asset_type"))
    if not itemtype:
        print("Unsupported asset_type:", asset.get("asset_type"))
        sys.exit(1)

    glpi = GLPI()
    try:
        glpi.auth()
    except Exception as e:
        print("GLPI auth failed:", e)
        sys.exit(1)

    try:
        payload = build_payload(glpi, asset)
        glpi.upsert(itemtype, payload)
        print("Asset synced OK")
    except Exception as e:
        print("Sync failed:", e)
        sys.exit(1)


if __name__ == "__main__":
    main()
