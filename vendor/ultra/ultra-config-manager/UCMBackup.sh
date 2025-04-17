#!/bin/bash

# Directory sorgente per il backup
source_dir="/home/fabio/libraries/UltraConfigManager"

# Ottieni la data e l'ora corrente per il nome della cartella di destinazione
timestamp=$(date +"%Y%m%d_%H%M")
backup_name="UCM_${timestamp}"

# Directory di destinazione su WSL
dest_dir_base="/mnt/c/wsl_backup/EGI_backup"
dest_dir="${dest_dir_base}/${backup_name}"

# Assicurati che la directory base di destinazione esista
mkdir -p "${dest_dir_base}"

# Crea la directory di backup
mkdir -p "${dest_dir}"

# Messaggio iniziale
echo "Avvio backup di UConfig nella directory ${dest_dir}"
echo "Avvio: $(date)"

# Utilizza rsync per copiare i file escludendo le directory da ignorare
rsync -av --progress \
    --exclude="vendor" \
    --exclude="node_modules" \
    --exclude=".*" \
    --exclude="storage/framework" \
    --exclude="public" \
    "${source_dir}/" "${dest_dir}/"

# Crea un file di log con i dettagli del backup
echo "Backup UConfig completato: $(date)" > "${dest_dir}/backup_info.txt"
echo "Sorgente: ${source_dir}" >> "${dest_dir}/backup_info.txt"
echo "Directory escluse: vendor, node_modules, .*, storage/framework, public" >> "${dest_dir}/backup_info.txt"

# Opzionale: crea un file zip di tutto il backup
zip_file="${dest_dir_base}/${backup_name}.zip"
(cd "${dest_dir_base}" && zip -r "${zip_file}" "${backup_name}")

# Messaggio finale
echo "Backup completato con successo!"
echo "Destinazione: ${dest_dir}"
echo "File ZIP: ${zip_file}"
echo "Completato: $(date)"