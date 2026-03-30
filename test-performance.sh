#!/bin/bash

# Script para testear mejoras de rendimiento en páginas de eventos
# Autor: Optimización Rutas Rurales
# Uso: ./test-performance.sh [opción]

echo "=== Test de Rendimiento - Páginas de Eventos ==="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# URL del evento específico
EVENT_URL="https://www.rutasrurales.io/evento/arde-lucus-2026-lugo-eventos-previos"

# Función para verificar si un comando existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Función para medir tiempo de carga
measure_load_time() {
    local url=$1
    local name=$2
    
    echo -e "${BLUE}📊 Test de tiempo de carga: ${name}${NC}"
    echo -e "URL: ${url}"
    
    if command_exists curl; then
        echo -e "${YELLOW}Midiendo con curl...${NC}"
        
        # Medir tiempo total
        start_time=$(date +%s%N)
        curl_output=$(curl -s -o /dev/null -w "HTTP Code: %{http_code}\nTiempo Total: %{time_total}s\nTamaño Descargado: %{size_download} bytes\nTiempo Connect: %{time_connect}s\nTiempo Start Transfer: %{time_starttransfer}s\n" "$url")
        end_time=$(date +%s%N)
        elapsed=$(( (end_time - start_time) / 1000000 ))
        
        echo -e "$curl_output"
        echo -e "Tiempo total medido: ${elapsed}ms"
        
        # Interpretación de resultados
        time_total=$(echo "$curl_output" | grep "Tiempo Total" | cut -d: -f2 | tr -d 's' | xargs)
        if (( $(echo "$time_total < 2" | bc -l 2>/dev/null || echo "0") )); then
            echo -e "${GREEN}✅ Excelente: Menos de 2 segundos${NC}"
        elif (( $(echo "$time_total < 3" | bc -l 2>/dev/null || echo "0") )); then
            echo -e "${GREEN}✅ Bueno: Menos de 3 segundos${NC}"
        elif (( $(echo "$time_total < 5" | bc -l 2>/dev/null || echo "0") )); then
            echo -e "${YELLOW}⚠️  Aceptable: Menos de 5 segundos${NC}"
        else
            echo -e "${RED}❌ Necesita mejora: Más de 5 segundos${NC}"
        fi
    else
        echo -e "${RED}curl no encontrado. Instalar con: sudo apt install curl${NC}"
    fi
    echo ""
}

# Función para verificar headers de cache
check_cache_headers() {
    local url=$1
    local name=$2
    
    echo -e "${BLUE}🔍 Verificando headers de cache: ${name}${NC}"
    
    if command_exists curl; then
        echo -e "Headers HTTP relevantes:"
        headers=$(curl -s -I "$url")
        
        echo "$headers" | grep -i "cache-control\|expires\|last-modified\|etag" || echo "No se encontraron headers de cache"
        
        # Verificar compresión
        echo -e "\n${YELLOW}Verificando compresión:${NC}"
        echo "$headers" | grep -i "content-encoding" || echo "No se encontró header de compresión"
        
    else
        echo -e "${RED}curl no encontrado${NC}"
    fi
    echo ""
}

# Función para verificar tamaño de página
check_page_size() {
    local url=$1
    local name=$2
    
    echo -e "${BLUE}📏 Verificando tamaño de página: ${name}${NC}"
    
    if command_exists curl; then
        # Tamaño total
        size=$(curl -s -o /dev/null -w "%{size_download}" "$url")
        size_kb=$((size / 1024))
        
        echo -e "Tamaño total descargado: ${size_kb} KB"
        
        # Interpretación
        if [ $size_kb -lt 500 ]; then
            echo -e "${GREEN}✅ Excelente: Menos de 500KB${NC}"
        elif [ $size_kb -lt 1000 ]; then
            echo -e "${GREEN}✅ Bueno: Menos de 1MB${NC}"
        elif [ $size_kb -lt 2000 ]; then
            echo -e "${YELLOW}⚠️  Aceptable: Menos de 2MB${NC}"
        else
            echo -e "${RED}❌ Necesita mejora: Más de 2MB${NC}"
        fi
        
    else
        echo -e "${RED}curl no encontrado${NC}"
    fi
    echo ""
}

# Función para verificar accesibilidad básica
check_accessibility() {
    local url=$1
    local name=$2
    
    echo -e "${BLUE}♿ Verificando accesibilidad básica: ${name}${NC}"
    
    if command_exists curl; then
        html_content=$(curl -s "$url")
        
        # Verificar alt text en imágenes
        img_count=$(echo "$html_content" | grep -c '<img')
        img_with_alt=$(echo "$html_content" | grep -c 'alt="[^"]*"')
        
        echo -e "Imágenes totales: $img_count"
        echo -e "Imágenes con alt text: $img_with_alt"
        
        if [ "$img_count" -gt 0 ]; then
            percentage=$((img_with_alt * 100 / img_count))
            echo -e "Porcentaje con alt text: ${percentage}%"
            
            if [ "$percentage" -ge 90 ]; then
                echo -e "${GREEN}✅ Excelente: Más del 90% de imágenes tienen alt text${NC}"
            elif [ "$percentage" -ge 70 ]; then
                echo -e "${YELLOW}⚠️  Aceptable: Más del 70% de imágenes tienen alt text${NC}"
            else
                echo -e "${RED}❌ Necesita mejora: Menos del 70% de imágenes tienen alt text${NC}"
            fi
        fi
        
        # Verificar ARIA labels
        aria_count=$(echo "$html_content" | grep -c 'aria-label="[^"]*"')
        echo -e "Elementos con aria-label: $aria_count"
        
        # Verificar landmarks ARIA
        landmarks=$(echo "$html_content" | grep -c 'role="\(banner\|main\|navigation\|contentinfo\|complementary\|search\)"')
        echo -e "Landmarks ARIA: $landmarks"
        
        # Verificar HTML semántico
        semantic_tags=$(echo "$html_content" | grep -c '<\(header\|nav\|main\|section\|article\|aside\|footer\)')
        echo -e "Etiquetas semánticas: $semantic_tags"
        
    else
        echo -e "${RED}curl no encontrado${NC}"
    fi
    echo ""
}

# Función para mostrar resumen de archivos optimizados
show_optimized_files() {
    echo -e "${GREEN}=== ARCHIVOS OPTIMIZADOS CREADOS ===${NC}"
    echo ""
    
    if [ -f "evento-optimizado.html" ]; then
        echo -e "1. ${GREEN}evento-optimizado.html${NC} - Plantilla principal optimizada"
        size=$(stat -c%s "evento-optimizado.html" 2>/dev/null || stat -f%z "evento-optimizado.html")
        echo "   Tamaño: $((size / 1024)) KB"
    else
        echo -e "1. ${RED}evento-optimizado.html${NC} - NO ENCONTRADO"
    fi
    
    if [ -f "css/evento-optimizado.css" ]; then
        echo -e "2. ${GREEN}css/evento-optimizado.css${NC} - Estilos optimizados"
        size=$(stat -c%s "css/evento-optimizado.css" 2>/dev/null || stat -f%z "css/evento-optimizado.css")
        echo "   Tamaño: $((size / 1024)) KB"
    else
        echo -e "2. ${RED}css/evento-optimizado.css${NC} - NO ENCONTRADO"
    fi
    
    if [ -f "OPTIMIZACION_EVENTOS.md" ]; then
        echo -e "3. ${GREEN}OPTIMIZACION_EVENTOS.md${NC} - Documentación completa"
        size=$(stat -c%s "OPTIMIZACION_EVENTOS.md" 2>/dev/null || stat -f%z "OPTIMIZACION_EVENTOS.md")
        echo "   Tamaño: $((size / 1024)) KB"
    else
        echo -e "3. ${RED}OPTIMIZACION_EVENTOS.md${NC} - NO ENCONTRADO"
    fi
    
    echo -e "4. ${GREEN}test-performance.sh${NC} - Script de testing (este archivo)"
    echo ""
}

# Función para mostrar próximos pasos
show_next_steps() {
    echo -e "${YELLOW}=== PRÓXIMOS PASOS RECOMENDADOS ===${NC}"
    echo ""
    echo "1. ${GREEN}Revisar archivos optimizados${NC}"
    echo "   - Leer OPTIMIZACION_EVENTOS.md para entender las mejoras"
    echo "   - Revisar evento-optimizado.html para ver la estructura"
    echo ""
    echo "2. ${GREEN}Implementar en producción${NC}"
    echo "   - cp evento-optimizado.html evento-detalle.html"
    echo "   - Añadir reglas de cache al .htaccess (ver documentación)"
    echo "   - Optimizar API PHP con headers de cache"
    echo ""
    echo "3. ${GREEN}Testear resultados${NC}"
    echo "   - Usar este script periódicamente"
    echo "   - Ejecutar Google Lighthouse"
    echo "   - Monitorear métricas reales de usuarios"
    echo ""
    echo "4. ${GREEN}Mantener optimizaciones${NC}"
    echo "   - Revisar trimestralmente con Lighthouse"
    echo "   - Optimizar nuevas imágenes automáticamente"
    echo "   - Actualizar dependencias regularmente"
    echo ""
}

# Menú principal
main() {
    clear
    echo "========================================="
    echo "   TEST DE OPTIMIZACIÓN - RUTAS RURALES   "
    echo "========================================="
    echo ""
    
    echo "Seleccione una opción:"
    echo "1) Test de rendimiento completo"
    echo "2) Verificar headers de cache"
    echo "3) Verificar tamaño de página"
    echo "4) Verificar accesibilidad"
    echo "5) Mostrar archivos optimizados"
    echo "6) Mostrar próximos pasos"
    echo "7) Todas las pruebas"
    echo "8) Salir"
    echo ""
    
    read -p "Opción [1-8]: " choice
    
    case $choice in
        1)
            measure_load_time "$EVENT_URL" "Evento en producción"
            ;;
        2)
            check_cache_headers "$EVENT_URL" "Evento en producción"
            ;;
        3)
            check_page_size "$EVENT_URL" "Evento en producción"
            ;;
        4)
            check_accessibility "$EVENT_URL" "Evento en producción"
            ;;
        5)
            show_optimized_files
            ;;
        6)
            show_next_steps
            ;;
        7)
            echo -e "${GREEN}=== EJECUTANDO TODAS LAS PRUEBAS ===${NC}\n"
            measure_load_time "$EVENT_URL" "Evento en producción"
            check_cache_headers "$EVENT_URL" "Evento en producción"
            check_page_size "$EVENT_URL" "Evento en producción"
            check_accessibility "$EVENT_URL" "Evento en producción"
            show_optimized_files
            show_next_steps
            ;;
        8)
            echo "Saliendo..."
            exit 0
            ;;
        *)
            echo -e "${RED}Opción no válida. Intente de nuevo.${NC}"
            sleep 2
            main
            ;;
    esac
    
    # Preguntar si desea continuar
    echo ""
    read -p "¿Desea realizar otra prueba? (s/n): " continue
    if [[ $continue == "s" || $continue == "S" ]]; then
        main
    else
        echo ""
        echo -e "${GREEN}Gracias por usar el test de optimización.${NC}"
        echo "Recuerde revisar la documentación en OPTIMIZACION_EVENTOS.md"
    fi
}

# Verificar dependencias
echo -e "${YELLOW}Verificando dependencias...${NC}"
if ! command_exists curl; then
    echo -e "${RED}ADVERTENCIA: curl no encontrado.${NC}"
    echo -e "Algunas pruebas requieren curl. Instalar con:"
    echo -e "  Ubuntu/Debian: sudo apt install curl"
    echo -e "  macOS: brew install curl"
    echo -e "  Windows: choco install curl"
    echo ""
fi

# Hacer el script ejecutable
chmod +x "$0" 2>/dev/null

# Ejecutar menú principal
main