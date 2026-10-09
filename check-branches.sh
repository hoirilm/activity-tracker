#!/usr/bin/env bash
# ==============================================================================
# Script Name   : check-branches.sh
# Description   : Deteksi & komparasi branch (Local & Origin) terhadap branch target (main)
#                 untuk memudahkan pembersihan branch yang sudah merged / tidak terpakai.
# Author        : pair programmed with Antigravity
# Compatibility : macOS (Bash 3.2+ / Zsh) & Linux
# ==============================================================================

set -e

# --- Warna Output Terminal ---
if [ -t 1 ]; then
    COLOR_RESET="\033[0m"
    COLOR_BOLD="\033[1m"
    COLOR_DIM="\033[2m"
    COLOR_RED="\033[0;31m"
    COLOR_GREEN="\033[0;32m"
    COLOR_YELLOW="\033[0;33m"
    COLOR_BLUE="\033[0;34m"
    COLOR_MAGENTA="\033[0;35m"
    COLOR_CYAN="\033[0;36m"
    COLOR_WHITE="\033[1;37m"
    COLOR_BG_DARK="\033[48;5;236m"
else
    COLOR_RESET=""
    COLOR_BOLD=""
    COLOR_DIM=""
    COLOR_RED=""
    COLOR_GREEN=""
    COLOR_YELLOW=""
    COLOR_BLUE=""
    COLOR_MAGENTA=""
    COLOR_CYAN=""
    COLOR_WHITE=""
    COLOR_BG_DARK=""
fi

# --- Konfigurasi Default ---
TARGET_BRANCH="main"
REMOTE_NAME="origin"
DO_FETCH=0
SHOW_ALL=0
FILTER_PREFIX=""
INTERACTIVE_DELETE=0
ASSUME_YES=0

# Protected branches regex pattern (tidak boleh dihapus)
PROTECTED_PATTERN="^(main|master|staging|development|develop|release/.*|HEAD)$"

show_help() {
    cat << EOF
${COLOR_BOLD}PENGGUNAAN:${COLOR_RESET}
    ./check-branches.sh [OPSI]

${COLOR_BOLD}DESKRIPSI:${COLOR_RESET}
    Mengecek dan membandingkan branch feature/hotfix di local dan origin terhadap
    branch target (default: 'main'). Mendeteksi branch yang sudah di-merge, di-squash,
    atau upstream-nya sudah [gone], serta menyediakan perintah penghapusan yang aman.

${COLOR_BOLD}OPSI:${COLOR_RESET}
    -t, --target <branch>   Target branch untuk perbandingan (default: 'main')
    -r, --remote <name>     Nama remote git (default: 'origin')
    -f, --fetch             Jalankan 'git fetch -p' sebelum pengecekan agar remote selalu up to date
    -p, --prefix <filter>   Filter nama branch berdasarkan prefix (misal: 'feature/' atau 'hotfix/')
    -a, --all               Tampilkan semua branch (termasuk yang unmerged/masih aktif)
    -d, --delete            Jalankan menu interaktif untuk menghapus branch yang aman dihapus
    -y, --yes               Konfirmasi otomatis 'ya' untuk eksekusi penghapusan (gunakan hati-hati)
    -h, --help              Tampilkan bantuan ini

${COLOR_BOLD}CONTOH:${COLOR_RESET}
    ./check-branches.sh                     # Pengecekan default terhadap 'main'
    ./check-branches.sh -f                  # Fetch prune dulu, lalu cek
    ./check-branches.sh -t development      # Bandingkan terhadap 'development'
    ./check-branches.sh -p "feature/"       # Hanya cek branch dengan awalan 'feature/'
    ./check-branches.sh -d                  # Mode interaktif untuk hapus branch merged
EOF
    exit 0
}

# --- Parse Argumen CLI ---
while [ $# -gt 0 ]; do
    case "$1" in
        -t|--target)
            TARGET_BRANCH="$2"
            shift 2
            ;;
        -r|--remote)
            REMOTE_NAME="$2"
            shift 2
            ;;
        -f|--fetch)
            DO_FETCH=1
            shift
            ;;
        -p|--prefix)
            FILTER_PREFIX="$2"
            shift 2
            ;;
        -a|--all)
            SHOW_ALL=1
            shift
            ;;
        -d|--delete)
            INTERACTIVE_DELETE=1
            shift
            ;;
        -y|--yes)
            ASSUME_YES=1
            shift
            ;;
        -h|--help)
            show_help
            ;;
        *)
            echo -e "${COLOR_RED}Opsi tidak dikenal: $1${COLOR_RESET}"
            echo "Gunakan './check-branches.sh --help' untuk bantuan."
            exit 1
            ;;
    esac
done

# --- Validasi Git Repository ---
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo -e "${COLOR_RED}Error: Direktori ini bukan repositori git!${COLOR_RESET}"
    exit 1
fi

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "")

# Auto-detect target branch jika 'main' tidak ada, coba 'master'
if ! git rev-parse --verify "$TARGET_BRANCH" >/dev/null 2>&1; then
    if [ "$TARGET_BRANCH" = "main" ] && git rev-parse --verify "master" >/dev/null 2>&1; then
        TARGET_BRANCH="master"
    else
        echo -e "${COLOR_RED}Error: Target branch '$TARGET_BRANCH' tidak ditemukan di repositori local!${COLOR_RESET}"
        exit 1
    fi
fi

# Fetch remote jika diminta
if [ $DO_FETCH -eq 1 ]; then
    echo -e "${COLOR_CYAN}🔄 Menjalankan 'git fetch -p $REMOTE_NAME' ...${COLOR_RESET}"
    if git fetch -p "$REMOTE_NAME" 2>/dev/null; then
        echo -e "${COLOR_GREEN}✓ Remote branch berhasil disinkronkan.${COLOR_RESET}\n"
    else
        echo -e "${COLOR_YELLOW}⚠️ Gagal fetch dari '$REMOTE_NAME', melanjutkan dengan data lokal terkini.${COLOR_RESET}\n"
    fi
fi

# Ambil target tree hash untuk mendeteksi squash merge
TARGET_TREE=$(git rev-parse "${TARGET_BRANCH}^{tree}" 2>/dev/null || echo "")
REMOTE_TARGET="${REMOTE_NAME}/${TARGET_BRANCH}"
REMOTE_TARGET_TREE=""
if git rev-parse --verify "$REMOTE_TARGET" >/dev/null 2>&1; then
    REMOTE_TARGET_TREE=$(git rev-parse "${REMOTE_TARGET}^{tree}" 2>/dev/null || echo "")
fi

# Ambil daftar branch yang merged secara langsung
# Simpan dalam string dengan spasi sebagai separator agar kompatibel dengan bash 3.2
MERGED_LOCAL_STR=" $(git branch --merged "$TARGET_BRANCH" | sed 's/^[ *]*//' | tr '\n' ' ') "
MERGED_REMOTE_STR=""
if git rev-parse --verify "$REMOTE_TARGET" >/dev/null 2>&1; then
    MERGED_REMOTE_STR=" $(git branch -r --merged "$REMOTE_TARGET" | sed 's/^[ *]*//' | tr '\n' ' ') "
fi

# Banner Header
echo -e "${COLOR_BOLD}${COLOR_CYAN}========================================================================${COLOR_RESET}"
echo -e "${COLOR_BOLD}${COLOR_WHITE}           GIT BRANCH DETECTOR & CLEANUP ASSISTANT                     ${COLOR_RESET}"
echo -e "${COLOR_BOLD}${COLOR_CYAN}========================================================================${COLOR_RESET}"
echo -e " Target Branch   : ${COLOR_GREEN}${COLOR_BOLD}${TARGET_BRANCH}${COLOR_RESET}"
echo -e " Remote Name     : ${COLOR_BLUE}${REMOTE_NAME}${COLOR_RESET}"
echo -e " Current Branch  : ${COLOR_YELLOW}${CURRENT_BRANCH}${COLOR_RESET}"
if [ -n "$FILTER_PREFIX" ]; then
    echo -e " Branch Filter   : ${COLOR_MAGENTA}${FILTER_PREFIX}*${COLOR_RESET}"
fi
echo -e "${COLOR_CYAN}------------------------------------------------------------------------${COLOR_RESET}"

# Inisialisasi daftar branch (Bash 3.2 index-based arrays)
LOCAL_MERGED_BRANCHES=()
LOCAL_SQUASHED_BRANCHES=()
LOCAL_GONE_BRANCHES=()
LOCAL_UNMERGED_BRANCHES=()

REMOTE_MERGED_BRANCHES=()
REMOTE_SQUASHED_BRANCHES=()
REMOTE_UNMERGED_BRANCHES=()

# ==============================================================================
# 1. ANALISIS LOCAL BRANCHES
# ==============================================================================
echo -e "\n${COLOR_BOLD}${COLOR_WHITE}🔍 1. Menganalisis Branch Lokal...${COLOR_RESET}"

# Membaca seluruh local branch dengan git for-each-ref
while IFS='|' read -r refname upstream uptrack cdate author subject; do
    [ -z "$refname" ] && continue

    # Lewati protected branches
    if echo "$refname" | grep -Eq "$PROTECTED_PATTERN"; then
        continue
    fi

    # Filter prefix jika ada
    if [ -n "$FILTER_PREFIX" ]; then
        case "$refname" in
            "$FILTER_PREFIX"*) ;;
            *) continue ;;
        esac
    fi

    # Cek status branch
    IS_MERGED=0
    IS_SQUASHED=0
    IS_GONE=0

    # Cek apakah upstream-nya sudah [gone]
    if [ "$uptrack" = "[gone]" ]; then
        IS_GONE=1
    fi

    # Cek apakah directly merged
    if [[ "$MERGED_LOCAL_STR" =~ [[:space:]]"$refname"[[:space:]] ]]; then
        IS_MERGED=1
    elif [ -n "$TARGET_TREE" ]; then
        # Cek squash merge via git merge-tree
        MTREE=$(git merge-tree --write-tree "$TARGET_BRANCH" "$refname" 2>/dev/null || true)
        if [ -n "$MTREE" ] && [ "$MTREE" = "$TARGET_TREE" ]; then
            IS_SQUASHED=1
        fi
    fi

    # Ambil commit count ahead / behind
    COUNTS=$(git rev-list --left-right --count "${TARGET_BRANCH}...${refname}" 2>/dev/null || echo "0 0")
    BEHIND=$(echo "$COUNTS" | awk '{print $1}')
    AHEAD=$(echo "$COUNTS" | awk '{print $2}')

    # Klasifikasi
    if [ $IS_MERGED -eq 1 ]; then
        LOCAL_MERGED_BRANCHES+=("${refname}|${cdate}|${author}|${AHEAD}|${BEHIND}|${subject}")
    elif [ $IS_SQUASHED -eq 1 ]; then
        LOCAL_SQUASHED_BRANCHES+=("${refname}|${cdate}|${author}|${AHEAD}|${BEHIND}|${subject}")
    elif [ $IS_GONE -eq 1 ]; then
        LOCAL_GONE_BRANCHES+=("${refname}|${cdate}|${author}|${AHEAD}|${BEHIND}|${subject}")
    else
        LOCAL_UNMERGED_BRANCHES+=("${refname}|${cdate}|${author}|${AHEAD}|${BEHIND}|${subject}")
    fi

done < <(git for-each-ref --format='%(refname:short)|%(upstream:short)|%(upstream:track)|%(committerdate:relative)|%(authorname)|%(subject)' refs/heads/)

# Tampilkan hasil lokal
TOTAL_LOCAL_DELETABLE=$((${#LOCAL_MERGED_BRANCHES[@]} + ${#LOCAL_SQUASHED_BRANCHES[@]} + ${#LOCAL_GONE_BRANCHES[@]}))

if [ $TOTAL_LOCAL_DELETABLE -eq 0 ]; then
    echo -e " ${COLOR_GREEN}✓ Bersih! Tidak ada local branch yang sudah merged atau obsolete.${COLOR_RESET}"
else
    echo -e " ${COLOR_YELLOW}Ditemukan $TOTAL_LOCAL_DELETABLE local branch yang siap dibersihkan:${COLOR_RESET}"
    
    # 1.1 Local Merged
    for item in "${LOCAL_MERGED_BRANCHES[@]}"; do
        IFS='|' read -r bname bdate bauthor bahead bbehind bsubj <<< "$item"
        CURRENT_MARK=""
        if [ "$bname" = "$CURRENT_BRANCH" ]; then
            CURRENT_MARK=" ${COLOR_RED}(SEDANG AKTIF)${COLOR_RESET}"
        fi
        printf "  ${COLOR_GREEN}[MERGED]${COLOR_RESET}   %-38s ${COLOR_DIM}%-18s %-16s${COLOR_RESET}%s\n" "$bname" "$bdate" "$bauthor" "$CURRENT_MARK"
    done

    # 1.2 Local Squashed
    for item in "${LOCAL_SQUASHED_BRANCHES[@]}"; do
        IFS='|' read -r bname bdate bauthor bahead bbehind bsubj <<< "$item"
        CURRENT_MARK=""
        if [ "$bname" = "$CURRENT_BRANCH" ]; then
            CURRENT_MARK=" ${COLOR_RED}(SEDANG AKTIF)${COLOR_RESET}"
        fi
        printf "  ${COLOR_YELLOW}[SQUASHED]${COLOR_RESET} %-38s ${COLOR_DIM}%-18s %-16s${COLOR_RESET}%s\n" "$bname" "$bdate" "$bauthor" "$CURRENT_MARK"
    done

    # 1.3 Local Gone
    for item in "${LOCAL_GONE_BRANCHES[@]}"; do
        IFS='|' read -r bname bdate bauthor bahead bbehind bsubj <<< "$item"
        CURRENT_MARK=""
        if [ "$bname" = "$CURRENT_BRANCH" ]; then
            CURRENT_MARK=" ${COLOR_RED}(SEDANG AKTIF)${COLOR_RESET}"
        fi
        printf "  ${COLOR_MAGENTA}[GONE]${COLOR_RESET}     %-38s ${COLOR_DIM}%-18s %-16s (ahead: %s)${COLOR_RESET}%s\n" "$bname" "$bdate" "$bauthor" "$bahead" "$CURRENT_MARK"
    done
fi

# Tampilkan Unmerged jika opsi --all aktif
if [ $SHOW_ALL -eq 1 ] && [ ${#LOCAL_UNMERGED_BRANCHES[@]} -gt 0 ]; then
    echo -e "\n ${COLOR_DIM}Local Branches Masih Aktif / Belum Merged (${#LOCAL_UNMERGED_BRANCHES[@]}):${COLOR_RESET}"
    for item in "${LOCAL_UNMERGED_BRANCHES[@]}"; do
        IFS='|' read -r bname bdate bauthor bahead bbehind bsubj <<< "$item"
        printf "  ${COLOR_DIM}[ACTIVE]   %-38s %-18s %-16s (+%s/-%s)${COLOR_RESET}\n" "$bname" "$bdate" "$bauthor" "$bahead" "$bbehind"
    done
fi

# ==============================================================================
# 2. ANALISIS REMOTE BRANCHES (ORIGIN)
# ==============================================================================
echo -e "\n${COLOR_BOLD}${COLOR_WHITE}🌐 2. Menganalisis Branch di Remote (${REMOTE_NAME})...${COLOR_RESET}"

if [ -z "$REMOTE_TARGET_TREE" ]; then
    echo -e " ${COLOR_YELLOW}⚠️ Remote target '${REMOTE_TARGET}' tidak ditemukan. Melewati pengecekan remote.${COLOR_RESET}"
else
    # Membaca seluruh remote branch
    while IFS='|' read -r refname cdate author subject; do
        [ -z "$refname" ] && continue

        # refname format: origin/feature/... -> buang prefix remote name
        short_name="${refname#${REMOTE_NAME}/}"

        # Abaikan origin/HEAD atau origin itu sendiri
        if [ "$short_name" = "HEAD" ] || [ "$short_name" = "$REMOTE_NAME" ] || [ -z "$short_name" ]; then
            continue
        fi

        # Lewati protected branches
        if echo "$short_name" | grep -Eq "$PROTECTED_PATTERN"; then
            continue
        fi

        # Filter prefix jika ada
        if [ -n "$FILTER_PREFIX" ]; then
            case "$short_name" in
                "$FILTER_PREFIX"*) ;;
                *) continue ;;
            esac
        fi

        IS_REMOTE_MERGED=0
        IS_REMOTE_SQUASHED=0

        # Cek apakah directly merged
        if [[ "$MERGED_REMOTE_STR" =~ [[:space:]]"$refname"[[:space:]] ]]; then
            IS_REMOTE_MERGED=1
        elif [ -n "$REMOTE_TARGET_TREE" ]; then
            # Cek squash merge
            MTREE=$(git merge-tree --write-tree "$REMOTE_TARGET" "$refname" 2>/dev/null || true)
            if [ -n "$MTREE" ] && [ "$MTREE" = "$REMOTE_TARGET_TREE" ]; then
                IS_REMOTE_SQUASHED=1
            fi
        fi

        if [ $IS_REMOTE_MERGED -eq 1 ]; then
            REMOTE_MERGED_BRANCHES+=("${short_name}|${cdate}|${author}|${subject}")
        elif [ $IS_REMOTE_SQUASHED -eq 1 ]; then
            REMOTE_SQUASHED_BRANCHES+=("${short_name}|${cdate}|${author}|${subject}")
        else
            REMOTE_UNMERGED_BRANCHES+=("${short_name}|${cdate}|${author}|${subject}")
        fi

    done < <(git for-each-ref --format='%(refname:short)|%(committerdate:relative)|%(authorname)|%(subject)' "refs/remotes/${REMOTE_NAME}/")

    TOTAL_REMOTE_DELETABLE=$((${#REMOTE_MERGED_BRANCHES[@]} + ${#REMOTE_SQUASHED_BRANCHES[@]}))

    if [ $TOTAL_REMOTE_DELETABLE -eq 0 ]; then
        echo -e " ${COLOR_GREEN}✓ Bersih! Tidak ada remote branch yang sudah merged di '${REMOTE_NAME}'.${COLOR_RESET}"
    else
        echo -e " ${COLOR_YELLOW}Ditemukan $TOTAL_REMOTE_DELETABLE remote branch yang siap dibersihkan:${COLOR_RESET}"

        # 2.1 Remote Merged
        for item in "${REMOTE_MERGED_BRANCHES[@]}"; do
            IFS='|' read -r bname bdate bauthor bsubj <<< "$item"
            printf "  ${COLOR_GREEN}[MERGED]${COLOR_RESET}   %-38s ${COLOR_DIM}%-18s %-16s${COLOR_RESET}\n" "${REMOTE_NAME}/${bname}" "$bdate" "$bauthor"
        done

        # 2.2 Remote Squashed
        for item in "${REMOTE_SQUASHED_BRANCHES[@]}"; do
            IFS='|' read -r bname bdate bauthor bsubj <<< "$item"
            printf "  ${COLOR_YELLOW}[SQUASHED]${COLOR_RESET} %-38s ${COLOR_DIM}%-18s %-16s${COLOR_RESET}\n" "${REMOTE_NAME}/${bname}" "$bdate" "$bauthor"
        done
    fi

    # Tampilkan Unmerged jika opsi --all aktif
    if [ $SHOW_ALL -eq 1 ] && [ ${#REMOTE_UNMERGED_BRANCHES[@]} -gt 0 ]; then
        echo -e "\n ${COLOR_DIM}Remote Branches Masih Aktif / Belum Merged (${#REMOTE_UNMERGED_BRANCHES[@]}):${COLOR_RESET}"
        for item in "${REMOTE_UNMERGED_BRANCHES[@]}"; do
            IFS='|' read -r bname bdate bauthor bsubj <<< "$item"
            printf "  ${COLOR_DIM}[ACTIVE]   %-38s %-18s %-16s${COLOR_RESET}\n" "${REMOTE_NAME}/${bname}" "$bdate" "$bauthor"
        done
    fi
fi

# ==============================================================================
# 3. RINGKASAN & PERINTAH PENGHAPUSAN MANUAL
# ==============================================================================
echo -e "\n${COLOR_CYAN}------------------------------------------------------------------------${COLOR_RESET}"
echo -e "${COLOR_BOLD}${COLOR_WHITE}📊 RINGKASAN:${COLOR_RESET}"
echo -e "  • Local Merged / Squashed  : ${COLOR_GREEN}${#LOCAL_MERGED_BRANCHES[@]}${COLOR_RESET} branch (Merged) | ${COLOR_YELLOW}${#LOCAL_SQUASHED_BRANCHES[@]}${COLOR_RESET} branch (Squashed)"
echo -e "  • Local Obsolete (GONE)    : ${COLOR_MAGENTA}${#LOCAL_GONE_BRANCHES[@]}${COLOR_RESET} branch"
echo -e "  • Remote Merged / Squashed : ${COLOR_GREEN}${#REMOTE_MERGED_BRANCHES[@]}${COLOR_RESET} branch (Merged) | ${COLOR_YELLOW}${#REMOTE_SQUASHED_BRANCHES[@]}${COLOR_RESET} branch (Squashed)"
echo -e "  • Local Belum Merged       : ${COLOR_CYAN}${#LOCAL_UNMERGED_BRANCHES[@]}${COLOR_RESET} branch"

# Siapkan list nama branch untuk dieksekusi / dicetak
LOCAL_SAFE_DELETE_NAMES=()
for item in "${LOCAL_MERGED_BRANCHES[@]}"; do
    IFS='|' read -r bname _ <<< "$item"
    if [ "$bname" != "$CURRENT_BRANCH" ]; then
        LOCAL_SAFE_DELETE_NAMES+=("$bname")
    fi
done

LOCAL_FORCE_DELETE_NAMES=()
for item in "${LOCAL_SQUASHED_BRANCHES[@]}" "${LOCAL_GONE_BRANCHES[@]}"; do
    IFS='|' read -r bname _ <<< "$item"
    if [ "$bname" != "$CURRENT_BRANCH" ]; then
        LOCAL_FORCE_DELETE_NAMES+=("$bname")
    fi
done

REMOTE_DELETE_NAMES=()
for item in "${REMOTE_MERGED_BRANCHES[@]}" "${REMOTE_SQUASHED_BRANCHES[@]}"; do
    IFS='|' read -r bname _ <<< "$item"
    REMOTE_DELETE_NAMES+=("$bname")
done

echo -e "\n${COLOR_BOLD}${COLOR_WHITE}📋 PERINTAH HAPUS MANUAL (Tinggal Copy-Paste):${COLOR_RESET}"

HAS_ANY_COMMAND=0

# Perintah local safe delete
if [ ${#LOCAL_SAFE_DELETE_NAMES[@]} -gt 0 ]; then
    HAS_ANY_COMMAND=1
    echo -e "\n${COLOR_DIM}# 1. Hapus branch local yang sudah MERGED (aman):${COLOR_RESET}"
    echo -e "${COLOR_GREEN}git branch -d ${LOCAL_SAFE_DELETE_NAMES[*]}${COLOR_RESET}"
fi

# Perintah local force delete (untuk squashed / gone)
if [ ${#LOCAL_FORCE_DELETE_NAMES[@]} -gt 0 ]; then
    HAS_ANY_COMMAND=1
    echo -e "\n${COLOR_DIM}# 2. Hapus branch local yang SQUASHED / GONE (perlu -D):${COLOR_RESET}"
    echo -e "${COLOR_YELLOW}git branch -D ${LOCAL_FORCE_DELETE_NAMES[*]}${COLOR_RESET}"
fi

# Perintah remote delete
if [ ${#REMOTE_DELETE_NAMES[@]} -gt 0 ]; then
    HAS_ANY_COMMAND=1
    echo -e "\n${COLOR_DIM}# 3. Hapus branch di origin (${REMOTE_NAME}) yang sudah MERGED:${COLOR_RESET}"
    echo -e "${COLOR_CYAN}git push ${REMOTE_NAME} --delete ${REMOTE_DELETE_NAMES[*]}${COLOR_RESET}"
fi

if [ $HAS_ANY_COMMAND -eq 0 ]; then
    echo -e " ${COLOR_GREEN}Tidak ada perintah penghapusan yang diperlukan. Repositori sudah rapi!${COLOR_RESET}"
fi

# Peringatan jika current branch ikut terdeteksi
CURRENT_MERGED=0
for item in "${LOCAL_MERGED_BRANCHES[@]}" "${LOCAL_SQUASHED_BRANCHES[@]}" "${LOCAL_GONE_BRANCHES[@]}"; do
    IFS='|' read -r bname _ <<< "$item"
    if [ "$bname" = "$CURRENT_BRANCH" ]; then
        CURRENT_MERGED=1
        break
    fi
done

if [ $CURRENT_MERGED -eq 1 ]; then
    echo -e "\n${COLOR_YELLOW}⚠️ CATATAN: Branch yang sedang aktif (${CURRENT_BRANCH}) sudah ter-merge.${COLOR_RESET}"
    echo -e "   Pindah ke branch lain terlebih dahulu sebelum menghapusnya: ${COLOR_WHITE}git checkout ${TARGET_BRANCH}${COLOR_RESET}"
fi

# ==============================================================================
# 4. MODE INTERAKTIF PENGHAPUSAN (Aktif jika menggunakan flag -d / --delete)
# ==============================================================================
if [ $HAS_ANY_COMMAND -eq 1 ]; then
    if [ $INTERACTIVE_DELETE -eq 1 ]; then
        echo -e "\n${COLOR_CYAN}------------------------------------------------------------------------${COLOR_RESET}"
        echo -e "${COLOR_BOLD}${COLOR_WHITE}🛠️  MENU PENGHAPUSAN INTERAKTIF:${COLOR_RESET}"
        echo -e " [1] Hapus SEMUA local branch yang MERGED (${#LOCAL_SAFE_DELETE_NAMES[@]} branch)"
        echo -e " [2] Hapus SEMUA local branch yang GONE / SQUASHED (${#LOCAL_FORCE_DELETE_NAMES[@]} branch)"
        echo -e " [3] Hapus SEMUA remote branch di ${REMOTE_NAME} yang MERGED (${#REMOTE_DELETE_NAMES[@]} branch)"
        echo -e " [4] Konfirmasi SATU PER SATU setiap branch"
        echo -e " [5] Batal & Keluar"
        
        read -r -p "Pilihan Anda [1-5]: " MENU_SELECT

        case "$MENU_SELECT" in
            1)
                if [ ${#LOCAL_SAFE_DELETE_NAMES[@]} -eq 0 ]; then
                    echo "Tidak ada local merged branch untuk dihapus."
                else
                    echo -e "\nMenghapus local merged branches..."
                    git branch -d "${LOCAL_SAFE_DELETE_NAMES[@]}"
                    echo -e "${COLOR_GREEN}✓ Local merged branches berhasil dihapus!${COLOR_RESET}"
                fi
                ;;
            2)
                if [ ${#LOCAL_FORCE_DELETE_NAMES[@]} -eq 0 ]; then
                    echo "Tidak ada local squashed/gone branch untuk dihapus."
                else
                    echo -e "\nMenghapus local squashed/gone branches..."
                    git branch -D "${LOCAL_FORCE_DELETE_NAMES[@]}"
                    echo -e "${COLOR_GREEN}✓ Local squashed/gone branches berhasil dihapus!${COLOR_RESET}"
                fi
                ;;
            3)
                if [ ${#REMOTE_DELETE_NAMES[@]} -eq 0 ]; then
                    echo "Tidak ada remote merged branch untuk dihapus."
                else
                    echo -e "\n${COLOR_RED}PERINGATAN: Ini akan menghapus branch di remote origin!${COLOR_RESET}"
                    read -r -p "Yakin ingin melanjutkan push delete ke ${REMOTE_NAME}? [y/N]: " CONFIRM_REMOTE
                    case "$CONFIRM_REMOTE" in
                        [yY]|[yY][eE][sS])
                            git push "$REMOTE_NAME" --delete "${REMOTE_DELETE_NAMES[@]}"
                            echo -e "${COLOR_GREEN}✓ Remote branches berhasil dihapus!${COLOR_RESET}"
                            ;;
                        *)
                            echo "Dibatalkan."
                            ;;
                    esac
                fi
                ;;
            4)
                echo -e "\n${COLOR_BOLD}Konfirmasi penghapusan per-branch:${COLOR_RESET}"
                # Review local safe
                for b in "${LOCAL_SAFE_DELETE_NAMES[@]}"; do
                    read -r -p "Hapus local branch [MERGED] '$b'? [y/N]: " RESP
                    if [[ "$RESP" =~ ^[yY] ]]; then
                        git branch -d "$b"
                    fi
                done
                # Review local force
                for b in "${LOCAL_FORCE_DELETE_NAMES[@]}"; do
                    read -r -p "Hapus local branch [GONE/SQUASHED] '$b'? [y/N]: " RESP
                    if [[ "$RESP" =~ ^[yY] ]]; then
                        git branch -D "$b"
                    fi
                done
                # Review remote
                for b in "${REMOTE_DELETE_NAMES[@]}"; do
                    read -r -p "Hapus remote branch [${REMOTE_NAME}] '$b'? [y/N]: " RESP
                    if [[ "$RESP" =~ ^[yY] ]]; then
                        git push "$REMOTE_NAME" --delete "$b"
                    fi
                done
                echo -e "\n${COLOR_GREEN}✓ Selesai melakukan review per-branch!${COLOR_RESET}"
                ;;
            *)
                echo -e "${COLOR_DIM}Dibatalkan. Tidak ada branch yang dihapus.${COLOR_RESET}"
                ;;
        esac
    else
        echo -e "\n${COLOR_DIM}💡 TIP: Jalankan './check-branches.sh -d' jika ingin menu interaktif untuk menghapus otomatis.${COLOR_RESET}"
    fi
fi

echo -e "\n${COLOR_CYAN}========================================================================${COLOR_RESET}\n"
