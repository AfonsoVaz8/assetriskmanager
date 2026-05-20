#!/usr/bin/env python3

import json
import requests
import os

GLPI_URL = os.getenv("GLPI_URL")
CLIENT_ID = os.getenv("GLPI_CLIENT_ID")
CLIENT_SECRET = os.getenv("GLPI_CLIENT_SECRET")
GLPI_USER = os.getenv("GLPI_USER")
GLPI_PASS = os.getenv("GLPI_PASS")

ARM_URL = os.getenv("ARM_URL")
ARM_TOKEN = os.getenv("ARM_TOKEN")

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

    def endpoint(self, itemtype):
        return f"{self.base}/v2/Assets/Custom/{itemtype}"

    def find(self, itemtype, arm_id):
        r = self.session.get(self.endpoint(itemtype), headers=self.headers())
        r.raise_for_status()

        items = r.json()
        items = items if isinstance(items, list) else items.get("data", [])

        for i in items:
            if str(i.get("custom_fields", {}).get("arm_id")) == str(arm_id):
                return i.get("id")

        return None

    def create(self, itemtype, payload):
        r = self.session.post(
            self.endpoint(itemtype),
            headers=self.headers_json(),
            json=payload
        )
        if r.status_code >= 400:
            print("[CREATE ERROR]", r.status_code, r.text)
        r.raise_for_status()

    def update(self, itemtype, asset_id, payload):
        r = self.session.patch(
            self.endpoint(itemtype) + "/" + str(asset_id),
            headers=self.headers_json(),
            json=payload
        )
        if r.status_code >= 400:
            print("[UPDATE ERROR]", r.status_code, r.text)
        r.raise_for_status()

    def upsert(self, itemtype, payload):
        arm_id = payload["custom_fields"]["arm_id"]

        existing = self.find(itemtype, arm_id)

        if existing:
            self.update(itemtype, existing, payload)
        else:
            self.create(itemtype, payload)


def fetch_assets():
    r = requests.get(
        ARM_URL,
        headers={"Authorization": f"Bearer {ARM_TOKEN}", "Accept": "application/json"}
    )
    r.raise_for_status()
    return r.json().get("data", [])


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
        "name": asset.get("asset_name"),
        "serial": asset.get("sku"),
        "comment": asset.get("description"),

        "manufacturer": manufacturer_id,
        "location": location_id,

        "custom_fields": {
            "arm_id": asset.get("asset_id"),
            "version": asset.get("version"),
            "ip_address": asset.get("ip_address"),
            "mac_address": asset.get("mac_address"),
            "fqdn": asset.get("fqdn"),
            "total_appreciation": asset.get("total_appreciation")
        }
    }


def main():
    glpi = GLPI()
    glpi.auth()

    assets = fetch_assets()

    ok = 0
    skip = 0
    err = 0

    for asset in assets:

        itemtype = ASSET_TYPE_MAP.get(asset.get("asset_type"))
        if not itemtype:
            skip += 1
            continue

        try:
            payload = build_payload(glpi, asset)
            glpi.upsert(itemtype, payload)
            ok += 1
        except Exception as e:
            err += 1
            print("[ERROR]", str(e))

    print(f"OK={ok} SKIP={skip} ERR={err}")


if __name__ == "__main__":
    main()