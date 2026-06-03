import asyncio
import json
import aio_pika
import subprocess


RABBITMQ_URL = "amqp://admin:admin123@localhost/"
QUEUE_NAME = "asset.sync"


async def sync_asset(asset_id: int):

    print(f"🔄 A executar sync do asset {asset_id}")

    result = subprocess.run(
        [
            "python",
            "arm-to-glpi-asset-sync.py",
            "--id",
            str(asset_id)
        ],
        capture_output=True,
        text=True
    )

    print(result.stdout)

    if result.returncode != 0:
        print(result.stderr)
        raise Exception(
            f"Erro no sync script (code {result.returncode})"
        )

    print(f"✅ Asset {asset_id} sincronizado")


async def process_message(message: aio_pika.IncomingMessage):

    async with message.process(requeue=True):

        data = json.loads(message.body.decode())

        asset_id = data["asset_id"]
        event = data["event"]

        print(f"📥 Recebido: {data}")

        try:
            await sync_asset(asset_id)

        except Exception as e:
            print(f"❌ Erro: {e}")
            raise


async def main():

    connection = await aio_pika.connect_robust(RABBITMQ_URL)

    channel = await connection.channel()

    await channel.set_qos(prefetch_count=1)

    queue = await channel.declare_queue(
        QUEUE_NAME,
        durable=True
    )

    await queue.consume(process_message)

    print("👷 Worker à espera de mensagens...")

    await asyncio.Future()


asyncio.run(main())