#!/bin/bash
set -e

AI_URL="https://api.together.xyz/v1/chat/completions"
TEXT_MODEL="meta-llama/Llama-3.3-70B-Instruct-Turbo-Free"
CONCURRENT=1
TOGETHER_API_KEY="tgp_v1_wCtzaA-VRH7g8t1BjpuGJZ71Mxqkl4uDYEAYIUNBqCg"

FILE_PATH="$1"

if [[ -z "$FILE_PATH" ]]; then
  echo "Usage: $0 <file>"
  exit 1
fi

if [[ ! -f "$FILE_PATH" ]]; then
  echo "❌ File not found: $FILE_PATH"
  exit 1
fi

MIME_TYPE=$(file --mime-type -b "$FILE_PATH")
echo "📄 Detected file type: $MIME_TYPE"
echo "======================================================"
echo "Timestamp: $(date)"
echo

# Prepare data depending on file type
if [[ "$MIME_TYPE" == "application/pdf" ]]; then
  echo "🖼️ Converting PDF to images..."
  TMP_IMG_DIR=$(mktemp -d)

  # Convert each PDF page to PNG at 400 DPI for accurate OCR
  pdftoppm -r 200 -gray -aa no "$FILE_PATH" "$TMP_IMG_DIR/page" -png >/dev/null 2>&1

  echo "🔍 Running OCR on images..."
  TEXT=""
  for IMG in "$TMP_IMG_DIR"/*.png; do
    [[ -f "$IMG" ]] || continue
    echo "📄 OCR: $(basename "$IMG")"
    PAGE_TEXT=$(tesseract "$IMG" stdout -l eng+deu+ara 2>/dev/null || true)

    TEXT+="$PAGE_TEXT"$'\n'
  done

  rm -rf "$TMP_IMG_DIR"
  MODEL="$TEXT_MODEL"
  PROMPT="Extract only factual data from the document and return a single valid, compact JSON object.
    Keep strictly all:
    - Names, addresses, dates, numbers, IDs, tax data, recipients, costs, rates, percentages, amounts, money, and contact or banking details.
    Remove completely:
    - Any paragraphs or sentences explaining reasons, laws, legal rights, appeals, data protection, privacy, or instructions.
    Formatting rules:
    - Response must be in the same language as the document including the keys.
    - Response must always have 'Title' of the document.
    - Flatten the structure into one dimension key:value.
    - Output only raw JSON, no markdown or text, no explanations.

    \n$TEXT"


elif [[ "$MIME_TYPE" =~ image/ ]]; then
  echo "🖼️ Detected image file — extracting text with OCR..."
  OCR_TEXT=$(tesseract "$FILE_PATH" stdout -l eng+deu 2>/dev/null || true)

  if [[ -n "$OCR_TEXT" ]]; then
    echo "📝 OCR text extracted."
    MODEL="$TEXT_MODEL"
    PROMPT="Extract all information to only one json format as key and value. Your answer should be only the json data:\n$OCR_TEXT"
  fi

elif [[ "$MIME_TYPE" == "text/plain" ]]; then
  echo "📜 Plain text file detected."
  TEXT=$(cat "$FILE_PATH")
  MODEL="$TEXT_MODEL"
  PROMPT="Extract all information to only one json format as key and value. Your answer should be only the json data:\n$TEXT"
else
  echo "❌ Unsupported file type: $MIME_TYPE"
  exit 1
fi

# Function to send the request
make_request() {
  local id=$1
  local start_time=$(date +%s.%N)

  # Escape newlines and quotes for safe JSON
  local ESCAPED_PROMPT=$(echo "$PROMPT" | jq -Rs .)
  echo "📤 Sending text data to model..."
  AI_URL="https://api.together.xyz/v1/chat/completions"

  ESCAPED_PROMPT=$(jq -Rs . <<< "$PROMPT")

  response=$(curl -s -X POST "$AI_URL" \
    -H "Authorization: Bearer $TOGETHER_API_KEY" \
    -H "Content-Type: application/json" \
    -d "{
      \"model\": \"$MODEL\",
      \"messages\": [
        {
          \"role\": \"user\",
          \"content\": $ESCAPED_PROMPT
        }
      ],
      \"max_tokens\": 2000,
      \"temperature\": 0.2
    }")

  local end_time=$(date +%s.%N)
  local total_time=$(echo "$end_time - $start_time" | bc -l)

  # Try to extract the message content
  local actual_response=$(echo "$response" | jq -r '.choices[0].message.content // empty')

  if [[ -n "$actual_response" ]]; then
    echo "✅ Request $id: $(printf "%.2f" $total_time)s"
    echo "   Response: \"$actual_response\""
    echo
  else
    local error=$(echo "$response" | jq -r '.error.message // .message // "Unknown error"')
    echo "❌ Request $id: $(printf "%.2f" $total_time)s - ERROR: $error"
    echo
  fi
}

# Run
for i in $(seq 1 $CONCURRENT); do
  make_request $i
done

echo "🏁 Test completed!"
