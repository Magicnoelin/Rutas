<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test API Soria</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #c8e6c9; }
        .error { background: #ffcdd2; }
        pre { background: white; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico completo - API Soria</h1>
    
    <?php
    // Test 1: Llamada básica a la API sin filtros
    echo "<div class='test'>";
    echo "<h2>Test 1: API sin filtros (primera página)</h2>";
    $url1 = 'http://localhost/api/alojamientos.php?table=accommodations&page=1&limit=20';
    echo "<p><strong>URL:</strong> <code>$url1</code></p>";
    
    $ch = curl_init($url1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $response1 = curl_exec($ch);
    $httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode1</p>";
    
    if ($httpCode1 == 200) {
        $data1 = json_decode($response1, true);
        if ($data1 && isset($data1['data']['alojamientos'])) {
            $count = count($data1['data']['alojamientos']);
            $total = $data1['data']['pagination']['total_records'] ?? 0;
            echo "<p class='success'>✅ <strong>Alojamientos en página 1:</strong> $count de $total totales</p>";
            
            // Contar cuántos de Soria hay en esta primera página
            $soriaEnPagina1 = 0;
            foreach ($data1['data']['alojamientos'] as $aloj) {
                $prov = $aloj['Provincia'] ?? $aloj['provincia'] ?? $aloj['province'] ?? '';
                if ($prov === 'Soria') {
                    $soriaEnPagina1++;
                }
            }
            echo "<p><strong>De Soria en página 1:</strong> $soriaEnPagina1</p>";
        }
    } else {
        echo "<p class='error'>❌ Error en API</p>";
    }
    echo "</div>";
    
    // Test 2: API CON filtro de Soria
    echo "<div class='test'>";
    echo "<h2>Test 2: API CON filtro provincia=Soria</h2>";
    $url2 = 'http://localhost/api/alojamientos.php?table=accommodations&page=1&limit=20&provincia=Soria';
    echo "<p><strong>URL:</strong> <code>$url2</code></p>";
    
    $ch = curl_init($url2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $response2 = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode2</p>";
    
    if ($httpCode2 == 200) {
        $data2 = json_decode($response2, true);
        if ($data2 && isset($data2['data']['alojamientos'])) {
            $count = count($data2['data']['alojamientos']);
            $total = $data2['data']['pagination']['total_records'] ?? 0;
            $totalPages = $data2['data']['pagination']['total_pages'] ?? 0;
            
            if ($count > 0) {
                echo "<p class='success'>✅ <strong>Alojamientos de Soria encontrados:</strong> $count en esta página</p>";
                echo "<p class='success'>✅ <strong>Total de Soria:</strong> $total alojamientos ($totalPages páginas)</p>";
                
                echo "<h3>Listado:</h3><ol>";
                foreach ($data2['data']['alojamientos'] as $aloj) {
                    $nombre = $aloj['Nombre'] ?? $aloj['nombre'] ?? $aloj['name'] ?? 'Sin nombre';
                    $municipio = $aloj['Localidad'] ?? $aloj['localidad'] ?? $aloj['municipality'] ?? '';
                    echo "<li><strong>$nombre</strong> - $municipio</li>";
                }
                echo "</ol>";
            } else {
                echo "<p class='error'>❌ <strong>NO se encontraron alojamientos de Soria</strong></p>";
            }
        }
    } else {
        echo "<p class='error'>❌ Error en API: HTTP $httpCode2</p>";
    }
    echo "</div>";
    
    // Test 3: Probar JavaScript fetch simulation
    echo "<div class='test'>";
    echo "<h2>Test 3: Simulación de lo que hace el JavaScript</h2>";
    ?>
    <button onclick="testJavaScript()">Ejecutar Test JavaScript</button>
    <div id="jsResult"></div>
    
    <script>
        async function testJavaScript() {
            const resultDiv = document.getElementById('jsResult');
            resultDiv.innerHTML = '<p>⏳ Cargando...</p>';
            
            try {
                // Simular exactamente lo que hace alojamientos-turisticos.html
                const url = 'api/alojamientos.php?table=accommodations&page=1&limit=20&provincia=Soria';
                console.log('Llamando a:', url);
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    mode: 'cors'
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                console.log('Data received:', data);
                
                if (data.success && data.data && data.data.alojamientos) {
                    const count = data.data.alojamientos.length;
                    const total = data.data.pagination.total_records;
                    
                    resultDiv.innerHTML = `
                        <p class="success">✅ <strong>Éxito!</strong></p>
                        <p><strong>Alojamientos en página:</strong> ${count}</p>
                        <p><strong>Total de Soria:</strong> ${total}</p>
                        <p><strong>Nombres:</strong></p>
                        <ul>
                            ${data.data.alojamientos.map(a => `<li>${a.Nombre || a.nombre || a.name}</li>`).join('')}
                        </ul>
                    `;
                } else {
                    resultDiv.innerHTML = '<p class="error">❌ Estructura de respuesta inválida</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                resultDiv.innerHTML = `<p class="error">❌ <strong>Error:</strong> ${error.message}</p>`;
            }
        }
    </script>
    <?php
    echo "</div>";
    ?>
    
    <div class="test">
        <h2>Conclusión</h2>
        <p>Si el Test 2 muestra 16-17 alojamientos pero el Test 3 (JavaScript) falla o muestra solo 3, entonces el problema es:</p>
        <ul>
            <li><strong>CORS</strong> - El navegador está bloqueando la petición</li>
            <li><strong>Caché del navegador</strong> - Está usando datos antiguos</li>
            <li><strong>El frontend está usando el JSON local</strong> en lugar de la API</li>
        </ul>
    </div>
</body>
</html>
