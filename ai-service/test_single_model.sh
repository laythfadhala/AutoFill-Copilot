#!/bin/bash

# Test only smollm:135m with 20 concurrent requests
set -e

AI_URL="http://localhost:11434/api/generate"
MODEL="phi3:mini"
CONCURRENT=5
CONTAINER_NAME="autofill-ai-service"

# --------------------------------------------------
# Function: show memory usage (container + host)
# --------------------------------------------------
show_memory_usage() {
    local container_name="autofill-ai-service"

    echo "------------------------------------------------------"
    
    # Check if container exists and is running
    if docker ps --filter "name=$container_name" --format "{{.Names}}" | grep -q "^$container_name$"; then
        echo "📦 Container resource usage for $container_name:"
        # Docker container CPU + RAM
        docker stats --no-stream \
            --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}" \
            "$container_name"
    else
        echo "🖥️  Running locally (no container detected)"
    fi

    echo "🧠 Host memory usage:"
    if command -v free >/dev/null 2>&1; then
        # Linux
        free -h | awk '/Mem/{printf "Used: %s / Total: %s (%.1f%%)\n", $3, $2, ($3/$2)*100}'
    else
        # macOS fallback using vm_stat
        total=$(vm_stat | awk '/Pages free/ {free=$3} /Pages active/ {active=$3} /Pages inactive/ {inactive=$3} /Pages speculative/ {spec=$3} /Pages wired down/ {wired=$4} END {print (active+inactive+spec+wired+free)*4096/1073741824}')
        used=$(vm_stat | awk '/Pages active/ {active=$3} /Pages speculative/ {spec=$3} /Pages wired down/ {wired=$4} END {print (active+spec+wired)*4096/1073741824}')
        percent=$(echo "scale=1; $used/$total*100" | bc)
        printf "Used: %.2fGiB / Total: %.2fGiB (%.1f%%)\n" "$used" "$total" "$percent"
    fi

    echo "⚙️ Host CPU load averages (1m / 5m / 15m):"
    if command -v uptime >/dev/null 2>&1; then
        uptime | awk -F'load averages?: ' '{print $2}'
    fi

    echo "------------------------------------------------------"
}


# --------------------------------------------------
echo "🔥 Testing $MODEL with $CONCURRENT concurrent requests"
echo "======================================================"
echo "Timestamp: $(date)"
echo

show_memory_usage
echo

# Function to make a single request
make_request() {
    local id=$1
    local start_time=$(date +%s.%N)
    
    local response=$(curl -s -X POST "$AI_URL" \
        -H "Content-Type: application/json" \
        -d "{
            \"model\": \"$MODEL\",
            \"prompt\": \"Fill the blanks with random names 'Name: {blank} Email: {blank}'\",
            \"stream\": false,
            \"options\": {
                \"num_ctx\": 100,
                \"num_predict\": 20,
                \"temperature\": 0.1
            }
        }")
    
    local end_time=$(date +%s.%N)
    local total_time=$(echo "$end_time - $start_time" | bc -l)
    
    if echo "$response" | jq -e '.response' >/dev/null 2>&1; then
        local actual_response=$(echo "$response" | jq -r '.response')
        echo "✅ Request $id: $(printf "%.2f" $total_time)s - \"$actual_response\""
    else
        local error=$(echo "$response" | jq -r '.error // "Unknown error"')
        echo "❌ Request $id: $(printf "%.2f" $total_time)s - ERROR: $error"
    fi
}

echo "🚀 Launching $CONCURRENT concurrent requests..."
echo

pids=()
test_start=$(date +%s.%N)

for i in $(seq 1 $CONCURRENT); do
    make_request $i &
    pids+=($!)
done

echo "⏳ Waiting for all requests to complete..."
for pid in "${pids[@]}"; do
    wait $pid
done

test_end=$(date +%s.%N)
total_time=$(echo "$test_end - $test_start" | bc -l)
throughput=$(echo "scale=1; $CONCURRENT / $total_time" | bc -l)

echo
echo "📊 Results Summary:"
echo "   Model: $MODEL"
echo "   Total test time: $(printf "%.2f" $total_time) seconds"
echo "   Throughput: $(printf "%.1f" $throughput) requests/second"
echo "   Average time per request: $(echo "scale=2; $total_time / $CONCURRENT" | bc -l) seconds"
echo

show_memory_usage
echo "🏆 Test completed!"
