#!/bin/bash

# ===============================================================
# ORACODED COMPILATION SCRIPT - Ultra Ecosystem / AChaos Protocol
# Versione: 1.1
# Autore: Fabio + Padmin D. Curtis
# Scopo: Generazione file codice + index semantico interrogabile
# ===============================================================

# --- CONFIGURAZIONE BASE ---
project_code="UCM"
target_dir="/home/fabio/libraries/UltraConfigManager"
output_dir="/home/fabio/libraries/${project_code}Compiled"
shared_dir="/var/www/shared"
timestamp=$(date +"%Y%m%d%H%M%S")

# --- FILE DI OUTPUT ---
server_file="$output_dir/${project_code}_server_code.txt"
client_file="$output_dir/${project_code}_client_code.txt"
config_file="$output_dir/${project_code}_config_misc.txt"
log_file="$output_dir/${project_code}_compilation_log.txt"
hash_file="$output_dir/${project_code}_file_hashes.txt"
prev_hash_file="$output_dir/${project_code}_file_hashes_prev.txt"
modified_files_file="$shared_dir/${project_code}_modified_files.txt"
index_file="$output_dir/${project_code}_semantic_index.json"

mkdir -p "$output_dir" "$shared_dir"

# --- BACKUP HASH ---
[ -f "$hash_file" ] && mv "$hash_file" "$prev_hash_file"

# --- FUNZIONE: AGGIUNGI FILE ---
add_file_to_output() {
    local file="$1"
    local output="$2"
    local category="$3"
    echo -e "\n######## File: $file ########\n" >> "$output"
    cat "$file" >> "$output"
    echo -e "\n" >> "$output"
    echo "File aggiunto a $category: $file" >> "$log_file"
    echo "{\"file\": \"$file\", \"category\": \"$category\" }" >> "$index_file.tmp"
}

# --- INIZIO LOG ---
echo "Inizio compilazione $project_code: $(date)" > "$log_file"
echo "[" > "$index_file.tmp"

# --- SCANSIONE FILE ---
find "$target_dir" \
    -type d \( -path "*/vendor" -o -path "*/node_modules" -o -path "*/.*" -o -path "*/storage" -o -path "*/public" \) -prune -o \
    -type f \( -name "*.php" -o -name "*.ts" -o -name "*.js" -o -name "*.env" -o -name "*.config.js" -o -name "*.blade.php" -o -name "*.css" -o -name "*.ico" -o -name "*.txt" -o -name "*.json" -o -name "*.stub" \) -print | sort | while read -r file; do

    if [[ "$file" == *.php && "$file" != *.blade.php ]]; then
        add_file_to_output "$file" "$server_file" "Server"
    elif [[ "$file" == *.blade.php ]]; then
        add_file_to_output "$file" "$config_file" "Config"
    elif [[ "$file" == *.js || "$file" == *.ts || "$file" == *.css ]]; then
        add_file_to_output "$file" "$client_file" "Client"
    else
        add_file_to_output "$file" "$config_file" "Config"
    fi

done

# --- CHIUDI JSON ---
sed -i '$ s/},/}/' "$index_file.tmp"
echo "]" >> "$index_file.tmp"
mv "$index_file.tmp" "$index_file"

# --- HASHING E CONFRONTO ---
echo "### Hash dei File Inclusi in $project_code ###" > "$hash_file"
find "$target_dir" -type f \( -name "*.php" -o -name "*.ts" -o -name "*.js" -o -name "*.env" -o -name "*.blade.php" -o -name "*.css" -o -name "*.ico" -o -name "*.txt" -o -name "*.json" -o -name "*.stub" \) | sort | while read -r file; do
    hash=$(sha256sum "$file" | awk '{print $1}')
    echo "$file: $hash" >> "$hash_file"
done

if [ -f "$prev_hash_file" ]; then
    echo "### File Modificati in $project_code ###" > "$modified_files_file"
    while IFS= read -r line; do
        current_file=$(echo "$line" | awk -F": " '{print $1}')
        current_hash=$(echo "$line" | awk -F": " '{print $2}')
        prev_hash=$(grep -F "$current_file" "$prev_hash_file" | awk -F": " '{print $2}')
        [ "$current_hash" != "$prev_hash" ] && echo "$current_file" >> "$modified_files_file"
    done < "$hash_file"
else
    echo "Nessun file hash precedente trovato per $project_code." > "$modified_files_file"
fi

# --- LOG FINALE ---
echo "Compilazione $project_code completata!" >> "$log_file"
echo "File server: $server_file" >> "$log_file"
echo "File client: $client_file" >> "$log_file"
echo "File config: $config_file" >> "$log_file"
echo "Log: $log_file" >> "$log_file"
echo "Hashes: $hash_file" >> "$log_file"
echo "Modifiche: $modified_files_file" >> "$log_file"
