#!/bin/bash

# Percorsi delle cartelle di backup
backup_root="/mnt/c/wsl_backup"  # Directory radice esistente
backup_base_dir="${backup_root}/EGI_backup"  # Sottodirectory per tutti i backup
timestamp=$(date +%Y%m%d_%H%M)
backup_dest_c="${backup_base_dir}/UUM_${timestamp}"  # Sottodirectory specifica per UUM
LOG_FILE="/home/fabio/libraries/UUCompiled/UUM_backup_log.txt"

# Directory sorgente (pacchetto UUM)
source_dir="/home/fabio/libraries/UltraUploadManager"

# Funzione per verificare se un drive è disponibile
check_drive() {
    local drive_path="$1"
    local drive_letter="$2"
    if [ ! -d "$(dirname "$drive_path")" ]; then
        echo "ATTENZIONE: Drive $drive_letter non disponibile, skip della copia" >> "$LOG_FILE"
        return 1
    fi
    return 0
}

# Percorsi delle cartelle di backup aggiuntive
drive_d="/mnt/d/Il mio Drive/EGI_backup/UUM_${timestamp}"
drive_e="/mnt/e/EGI_backup/UUM_${timestamp}"
drive_h="/mnt/h/EGI_backup/UUM_${timestamp}"

# Directory da escludere dal backup
exclude_dirs=(
    "--exclude=vendor/"
    "--exclude=node_modules/"
    "--exclude=.git/"
    "--exclude=.config/"
    "--exclude=.cache/"
    "--exclude=.history/"
    "--exclude=.vscode/"
    "--exclude=public/build/"  # Esclude i file compilati (es. app-5VKrzpql.js)
)

# Inizio log operazione
mkdir -p "$(dirname "$LOG_FILE")"  # Crea la directory del log se non esiste
echo "===== Inizio backup UUM $(date) =====" >> "$LOG_FILE"

# Verifica e creazione directory principale
if [ ! -d "$backup_root" ]; then
    echo "ERRORE: Directory radice di backup non accessibile: $backup_root" >> "$LOG_FILE"
    exit 1
fi

# Crea la sottodirectory EGI_backup se non esiste
if [ ! -d "$backup_base_dir" ]; then
    mkdir -p "$backup_base_dir"
    if [ $? -ne 0 ]; then
        echo "ERRORE: Impossibile creare la directory di backup: $backup_base_dir" >> "$LOG_FILE"
        exit 1
    fi
    echo "Creata directory di backup: $backup_base_dir" >> "$LOG_FILE"
fi

mkdir -p "$backup_dest_c"

# Verifica che la directory sorgente esista
if [ ! -d "$source_dir" ]; then
    echo "ERRORE: Directory sorgente non trovata: $source_dir" >> "$LOG_FILE"
    exit 1
fi

# Comando rsync principale
if rsync -avz "${exclude_dirs[@]}" "$source_dir/" "$backup_dest_c"; then
    echo "✓ Backup principale UUM completato in: $backup_dest_c" >> "$LOG_FILE"
else
    echo "✗ ERRORE nel backup principale UUM!" >> "$LOG_FILE"
    exit 1
fi

# Copia nelle altre destinazioni
# Drive D
if check_drive "$drive_d" "D:"; then
    mkdir -p "$drive_d"
    if rsync -av "$backup_dest_c/" "$drive_d"; then
        echo "✓ Copia UUM su D: completata in $drive_d" >> "$LOG_FILE"
    else
        echo "✗ ERRORE nella copia UUM su D:" >> "$LOG_FILE"
    fi
fi

# Drive E
if check_drive "$drive_e" "E:"; then
    mkdir -p "$drive_e"
