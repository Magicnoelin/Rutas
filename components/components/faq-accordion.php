<?php
/**
 * Componente visual para renderizar el bloque de FAQs con etiquetas HTML nativas (<details> y <summary>).
 * Cero JavaScript, ultraligero y compatible con accesibilidad.
 */

// Si no hay preguntas, el componente NO pinta absolutamente nada (0 HTML)
if (empty($faqs)) {
    return;
}

// Función helper local para permitir solo etiquetas HTML seguras en las respuestas
if (!function_exists('sanitizeFaqAnswer')) {
    function sanitizeFaqAnswer($html) {
        // Permitimos enlaces, negritas, cursivas, párrafos, listas y saltos de línea
        $allowedTags = '<a><strong><b><em><i><p><ul><ol><li><br>';
        return strip_tags($html, $allowedTags);
    }
}
?>

<!-- Estilos mínimos inline para no depender de archivos CSS externos (cero impacto en carga) -->
<style>
.faq-section {
    margin: 2.5rem 0;
    font-family: inherit;
}
.faq-section h2 {
    font-size: 1.5rem;
    margin-bottom: 1.25rem;
    color: #2c3e50;
}
.faq-item {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    background-color: #ffffff;
    overflow: hidden;
    transition: border-color 0.2s ease;
}
.faq-item[open] {
    border-color: #cbd5e1;
}
.faq-summary {
    padding: 1rem 1.25rem;
    font-weight: 600;
    font-size: 1.05rem;
    cursor: pointer;
    list-style: none;
    position: relative;
    user-select: none;
    color: #1e293b;
}
/* Oculta la flecha por defecto del navegador */
.faq-summary::-webkit-details-marker {
    display: none;
}
.faq-summary::after {
    content: '+';
    position: absolute;
    right: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.2rem;
    font-weight: 400;
    color: #64748b;
}
.faq-item[open] .faq-summary::after {
    content: '−';
}
.faq-answer {
    padding: 0 1.25rem 1.25rem 1.25rem;
    color: #475569;
    line-height: 1.6;
    font-size: 0.98rem;
    border-top: 1px solid #f1f5f9;
    margin-top: 0.5rem;
    padding-top: 1rem;
}
.faq-answer p:last-child {
    margin-bottom: 0;
}
</style>

<section class="faq-section" id="preguntas-frecuentes">
    <h2>Preguntas Frecuentes</h2>
    <div class="faq-list">
        <?php foreach ($faqs as $index => $faq): ?>
            <details class="faq-item" <?php echo $index === 0 ? 'open' : ''; ?>>
                <summary class="faq-summary">
                    <?php echo htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?>
                </summary>
                <div class="faq-answer">
                    <?php echo sanitizeFaqAnswer($faq['answer']); ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>