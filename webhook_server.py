#!/usr/bin/env python3
"""
Второй вебхук на сервере (первый проект — Natalis на :9000, этот — :9001).
POST http://<сервер>:9001/deploy → запуск deploy.sh в каталоге клона.

На сервере:
  chmod +x deploy.sh webhook_server.py
  ufw allow 9001/tcp   # при необходимости

  systemd: см. scripts/webhook-deploy-viktoria.service
"""

from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import subprocess

# Должно совпадать с путём в deploy.sh (клон репозитория на сервере)
PROJECT_DIR = "/var/www/DoctorSkripnikovaViktoria"
DEPLOY_SCRIPT = f"{PROJECT_DIR}/deploy.sh"
PORT = 9001


class WebhookHandler(BaseHTTPRequestHandler):
    def do_POST(self):
        if self.path != "/deploy":
            self.send_response(404)
            self.end_headers()
            return

        content_length = int(self.headers.get("Content-Length", 0))
        if content_length:
            self.rfile.read(content_length)  # тело не маршрутизиру — только запускаем deploy

        try:
            print("🚀 Webhook (Doctor) received, starting deployment...")
            result = subprocess.run(
                ["/bin/bash", DEPLOY_SCRIPT],
                capture_output=True,
                text=True,
                timeout=600,
            )

            if result.returncode != 0:
                print(result.stderr)
                self.send_response(500)
            else:
                self.send_response(200)

            self.send_header("Content-type", "application/json")
            self.end_headers()
            response = {
                "status": "success" if result.returncode == 0 else "error",
                "message": "Deployment run finished",
                "output": (result.stdout or "") + (result.stderr or ""),
            }
            self.wfile.write(json.dumps(response).encode())
            print("✅ deploy.sh finished" if result.returncode == 0 else "❌ deploy.sh failed")
            if result.stdout:
                print(result.stdout)
        except Exception as e:  # noqa: BLE001 — отдаём 500 в ответ вебхука
            print(f"❌ Error: {e}")
            self.send_response(500)
            self.send_header("Content-type", "application/json")
            self.end_headers()
            self.wfile.write(
                json.dumps({"status": "error", "message": str(e)}).encode()
            )

    def log_message(self, fmt, *args):
        print(f"[{self.log_date_time_string()}] {fmt % args}")


if __name__ == "__main__":
    server = HTTPServer(("0.0.0.0", PORT), WebhookHandler)
    print(f"🎯 Webhook (Doctor) on port {PORT} → {DEPLOY_SCRIPT}")
    print("📡 POST /deploy")
    server.serve_forever()
