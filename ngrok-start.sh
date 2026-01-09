#!/bin/bash

# Script per avviare ngrok e esporre il progetto Laravel su Herd
# Punterà a: http://sanvincenzocalcio.test/admin

# Colori per output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Avvio di ngrok per San Vincenzo Calcio${NC}\n"
echo -e "${BLUE}📍 Punterà a: http://sanvincenzocalcio.test/admin${NC}\n"

# Verifica se ngrok è installato
if ! command -v ngrok &> /dev/null; then
    echo -e "${YELLOW}❌ ngrok non è installato.${NC}"
    echo "Installa ngrok da: https://ngrok.com/download"
    exit 1
fi

# Verifica se l'utente è autenticato
if ! ngrok config check &> /dev/null; then
    echo -e "${YELLOW}⚠️  ngrok non è configurato.${NC}"
    echo "Esegui: ngrok config add-authtoken <TUO_TOKEN>"
    exit 1
fi

# Verifica se Herd è in esecuzione sulla porta 80
PORT=80
if lsof -Pi :80 -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo -e "${GREEN}✓${NC} Rilevato Herd/Valet su porta 80"
else
    echo -e "${YELLOW}⚠️  Nessun server rilevato sulla porta 80.${NC}"
    echo "Assicurati che Herd sia in esecuzione e che sanvincenzocalcio.test sia configurato."
    read -p "Vuoi continuare comunque? (s/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        exit 1
    fi
fi

echo -e "\n${GREEN}🌐 Avvio di ngrok sulla porta ${PORT}...${NC}"
echo -e "${BLUE}💡 L'URL ngrok ti permetterà di accedere a: https://xxxx.ngrok-free.app/admin${NC}\n"
echo -e "${BLUE}📝 Configurato per inoltrare le richieste a: sanvincenzocalcio.test${NC}\n"

# Avvia ngrok sulla porta 80 (Herd) con host-header riscritto
# Questo fa sì che Herd riconosca le richieste come se arrivassero da sanvincenzocalcio.test
ngrok http $PORT --host-header=rewrite --host-header=sanvincenzocalcio.test
