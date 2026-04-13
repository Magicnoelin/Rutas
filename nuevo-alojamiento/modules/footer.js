// footer.js - Modular footer component for nuevo-alojamiento pages
export async function loadFooter() {
    // Create footer element if it doesn't exist
    let footerElement = document.getElementById('footer');
    if (!footerElement) {
        footerElement = document.createElement('footer');
        footerElement.id = 'footer';
        document.body.appendChild(footerElement);
    }

    try {
        // Fetch footer content
        const response = await fetch('/footer.php');
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const html = await response.text();
        
        // Extract just the footer content (from <footer class="footer"> to </footer>)
        const footerMatch = html.match(/<footer\b[^>]*\bclass\s*=\s*["']footer["'][^>]*>.*?<\/footer>/is);
        if (footerMatch) {
            footerElement.innerHTML = footerMatch[0];
            
            // Load any necessary scripts
            loadFooterScripts();
            
            console.log('Footer loaded successfully');
        } else {
            console.warn('Footer not found in response');
            loadFallbackFooter(footerElement);
        }
    } catch (error) {
        console.error('Error loading footer:', error);
        loadFallbackFooter(footerElement);
    }
}

function loadFooterScripts() {
    // Load main script if not already loaded
    if (!document.querySelector('script[src*="script.js"]')) {
        const script = document.createElement('script');
        script.src = '/script.js?v=20260114';
        script.defer = true;
        document.body.appendChild(script);
    }
}

function loadFallbackFooter(footerElement) {
    footerElement.innerHTML = `
        <footer class="footer">
            <div class="container">
                <div class="footer-bottom">
                    <div class="footer-copyright">
                        <p>&copy; 2026 <strong>rutasrurales.io</strong>. Todos los derechos reservados.</p>
                    </div>
                </div>
            </div>
        </footer>
    `;
}

// Initialize footer when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => loadFooter());
} else {
    loadFooter();
}