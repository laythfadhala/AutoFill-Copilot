#!/bin/bash

# Start ollama server in the background
ollama serve &

# Wait for server to be ready with timeout
echo "Waiting for Ollama server to start..."
TIMEOUT=60  # 60 seconds timeout
COUNTER=0
while ! ollama list >/dev/null 2>&1; do
  sleep 1
  COUNTER=$((COUNTER + 1))
  if [ $COUNTER -ge $TIMEOUT ]; then
    echo "ERROR: Ollama server failed to start within $TIMEOUT seconds"
    exit 1
  fi
done

echo "Ollama server is ready!"

# Pull models
echo "Pulling phi3:mini model..."
ollama pull phi3:mini
echo "Pulling gemma3:1b model..."
ollama pull gemma3:1b
echo "Pulling gemma3:4b model..."
ollama pull gemma3:4b

echo "All models pulled successfully!"

# Keep the server running in foreground
wait