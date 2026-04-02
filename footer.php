<?php
// Detectar idioma desde variable $lang o desde el path
$lang = $lang ?? 'es';

// Traducciones del footer
$footer_translations = [
    'es' => [
        'contact' => 'Contacto',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'legal_notice' => 'Aviso Legal',
        'privacy' => 'Privacidad',
        'cookies' => 'Cookies',
        'acknowledgments' => 'Agradecimientos',
        'social_commitment' => 'Compromiso Social',
        'copyright' => '&copy; 2026 <strong>rutasrurales.io</strong>. Todos los derechos reservados.',
        'follow_us' => 'Síguenos',
        'eclipse_info' => 'Información Eclipse 2026',
        'accommodations' => 'Alojamientos',
        'places' => 'Lugares de interés',
        'events' => 'Eventos culturales',
        'activities' => 'Actividades turísticas'
    ],
    'en' => [
        'contact' => 'Contact',
        'email' => 'Email',
        'phone' => 'Phone',
        'legal_notice' => 'Legal Notice',
        'privacy' => 'Privacy',
        'cookies' => 'Cookies',
        'acknowledgments' => 'Acknowledgments',
        'social_commitment' => 'Social Commitment',
        'copyright' => '&copy; 2026 <strong>rutasrurales.io</strong>. All rights reserved.',
        'follow_us' => 'Follow us',
        'eclipse_info' => 'Eclipse 2026 Information',
        'accommodations' => 'Accommodations',
        'places' => 'Places of interest',
        'events' => 'Cultural events',
        'activities' => 'Tourist activities'
    ],
    'fr' => [
        'contact' => 'Contact',
        'email' => 'Email',
        'phone' => 'Téléphone',
        'legal_notice' => 'Mentions légales',
        'privacy' => 'Confidentialité',
        'cookies' => 'Cookies',
        'acknowledgments' => 'Remerciements',
        'social_commitment' => 'Engagement social',
        'copyright' => '&copy; 2026 <strong>rutasrurales.io</strong>. Tous droits réservés.',
        'follow_us' => 'Suivez-nous',
        'eclipse_info' => 'Informations Éclipse 2026',
        'accommodations' => 'Hébergements',
        'places' => 'Lieux d\'intérêt',
        'events' => 'Événements culturels',
        'activities' => 'Activités touristiques'
    ],
    'de' => [
        'contact' => 'Kontakt',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'legal_notice' => 'Impressum',
        'privacy' => 'Datenschutz',
        'cookies' => 'Cookies',
        'acknowledgments' => 'Danksagungen',
        'social_commitment' => 'Soziales Engagement',
        'copyright' => '&copy; 2026 <strong>rutasrurales.io</strong>. Alle Rechte vorbehalten.',
        'follow_us' => 'Folgen Sie uns',
        'eclipse_info' => 'Eclipse 2026 Informationen',
        'accommodations' => 'Unterkünfte',
        'places' => 'Sehenswürdigkeiten',
        'events' => 'Kulturelle Veranstaltungen',
        'activities' => 'Touristische Aktivitäten'
    ],
    'zh' => [
        'contact' => '联系',
        'email' => '电子邮件',
        'phone' => '电话',
        'legal_notice' => '法律声明',
        'privacy' => '隐私',
        'cookies' => 'Cookies',
        'acknowledgments' => '致谢',
        'social_commitment' => '社会责任',
        'copyright' => '&copy; 2026 <strong>rutasrurales.io</strong>. 保留所有权利。',
        'follow_us' => '关注我们',
        'eclipse_info' => '2026年日食信息',
        'accommodations' => '住宿',
        'places' => '景点',
        'events' => '文化活动',
        'activities' => '旅游活动'
    ]
];

$ft = $footer_translations[$lang] ?? $footer_translations['es'];
$lang_prefix = ($lang != 'es') ? '/' . $lang : '';
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-section">
                <h3><?php echo $ft['contact']; ?></h3>
                <div class="footer-info">
                    <span class="footer-item">
                        <i class="fas fa-envelope"></i> 
                        <a href="mailto:olgamarin@rutasrurales.io">olgamarin@rutasrurales.io</a>
                    </span>
                    <span class="footer-item">
                        <i class="fas fa-phone"></i> 
                        <a href="tel:+34605249696">+34 605 249 696</a>
                    </span>
                </div>
            </div>
            
            <div class="footer-section">
                <h3><?php echo $ft['follow_us']; ?></h3>
                <div class="footer-social">
                    <a href="https://www.facebook.com/rutasrurales.io" target="_blank" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/rutasrurales" target="_blank" aria-label="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.instagram.com/rutasrurales.io" target="_blank" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.linkedin.com/company/rutasrurales" target="_blank" aria-label="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3><?php echo $ft['eclipse_info']; ?></h3>
                <div class="footer-links">
                    <a href="<?php echo $lang_prefix; ?>/mapa-eclipse.php"><?php echo $ft['eclipse_info']; ?></a>
                    <a href="<?php echo $lang_prefix; ?>/alojamientos-turisticos.html"><?php echo $ft['accommodations']; ?></a>
                    <a href="<?php echo $lang_prefix; ?>/lugares-interes-paginacion.html"><?php echo $ft['places']; ?></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3><?php echo $ft['legal_notice']; ?></h3>
                <div class="footer-links">
                    <a href="<?php echo $lang_prefix; ?>/aviso-legal.html"><?php echo $ft['legal_notice']; ?></a>
                    <a href="<?php echo $lang_prefix; ?>/politica-privacidad.html"><?php echo $ft['privacy']; ?></a>
                    <a href="<?php echo $lang_prefix; ?>/politica-cookies.html"><?php echo $ft['cookies']; ?></a>
                    <a href="<?php echo $lang_prefix; ?>/agradecimientos.html"><?php echo $ft['acknowledgments']; ?></a>
                    <a href="<?php echo $lang_prefix; ?>/compromiso-social.html"><?php echo $ft['social_commitment']; ?></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-copyright">
                <p><?php echo $ft['copyright']; ?></p>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    background-color: #2c5f2d;
    color: white;
    padding: 25px 15px;
    font-family: 'Montserrat', sans-serif;
}

.footer .container {
    max-width: 1200px;
    margin: 0 auto;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.footer-section h3 {
    color: #d4a574;
    font-size: 1rem;
    margin-bottom: 12px;
    font-weight: 600;
}

.footer-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.footer-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
}

.footer-item i {
    color: #d4a574;
    width: 16px;
    text-align: center;
    font-size: 0.9rem;
}

.footer-item a {
    color: white;
    text-decoration: none;
    transition: color 0.3s;
    font-size: 0.85rem;
}

.footer-item a:hover {
    color: #d4a574;
}

.footer-social {
    display: flex;
    gap: 10px;
}

.footer-social a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: white;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 0.9rem;
}

.footer-social a:hover {
    background: #d4a574;
    transform: translateY(-2px);
}

.footer-links {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.footer-links a {
    color: white;
    text-decoration: none;
    font-size: 0.85rem;
    padding: 3px 0;
    transition: color 0.3s;
}

.footer-links a:hover {
    color: #d4a574;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 15px;
    text-align: center;
}

.footer-copyright {
    font-size: 0.7rem;
    opacity: 0.8;
}

.footer-copyright p {
    margin: 0;
}

/* Responsive design */
@media (max-width: 768px) {
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .footer {
        padding: 20px 10px;
    }
    
    .footer-section h3 {
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    .footer-item {
        font-size: 0.8rem;
    }
    
    .footer-links a {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}
</style>

<script src="script.js?v=20260114"></script>

</body>
</html>
