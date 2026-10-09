#!/usr/bin/env bash

# Script untuk menjalankan semua service sekaligus:
# 1. Laravel Web Server
# 2. Laravel Queue Worker
# 3. Laravel Reverb WebSocket
# 4. React Native (Expo) Mobile App

npx concurrently -k \
  -n "WEB,QUEUE,REVERB,MOBILE" \
  -c "blue.bold,green.bold,magenta.bold,yellow.bold" \
  "cd /Users/macanas/project/web-IECC && php artisan serve --host=0.0.0.0 --port=8000" \
  "cd /Users/macanas/project/web-IECC && php artisan queue:listen --tries=1 --timeout=0" \
  "cd /Users/macanas/project/web-IECC && php artisan reverb:start --host=0.0.0.0 --port=8080" \
  "cd /Users/macanas/project/app-IECC && npx expo start --lan"


