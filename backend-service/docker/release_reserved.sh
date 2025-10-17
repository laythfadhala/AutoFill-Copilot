#!/bin/sh

REDIS_HOST=${REDIS_HOST:-redis}
REDIS_PORT=${REDIS_PORT:-6379}

echo "🔁 Releasing reserved Redis jobs..."

# Wait until Redis is fully loaded and ready (not just responding to PING)
echo "⏳ Waiting for Redis to be fully ready..."
for i in {1..60}; do
  # Try a simple command that requires Redis to be fully loaded
  if redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" INFO server > /dev/null 2>&1; then
    echo "✅ Redis is fully ready!"
    break
  fi
  echo "Redis still loading... ($i/60)"
  sleep 2
done

if ! redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" INFO server > /dev/null 2>&1; then
  echo "❌ Redis failed to become ready after 120 seconds, skipping job release"
  exit 1
fi

redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" --scan --pattern '*:reserved' | while read -r key; do
  [ -z "$key" ] && continue
  main="${key%:reserved}"
  key_type=$(redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" TYPE "$key")

  if [ "$key_type" = "zset" ]; then
    # Move jobs from zset (Horizon)
    redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" ZRANGE "$key" 0 -1 | while read -r job; do
      [ -n "$job" ] && {
        redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" ZREM "$key" "$job" > /dev/null
        redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" LPUSH "$main" "$job" > /dev/null
      }
    done
  elif [ "$key_type" = "list" ]; then
    # Move jobs from list
    while redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" RPOPLPUSH "$key" "$main" > /dev/null; do :; done
  fi
done

echo "✅ Done releasing reserved jobs."
