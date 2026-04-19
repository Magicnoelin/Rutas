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
        <div class="footer-content-simple">
            <div class="footer-info">
                <span><i class="fas fa-envelope"></i> <a href="mailto:olgamarin@rutasrurales.io" style="color:inherit; text-decoration:none;">olgamarin@rutasrurales.io</a></span>
                <span><i class="fas fa-phone"></i> <a href="tel:+34605249696" style="color:inherit; text-decoration:none;">+34 605 249 696</a></span>
            </div>
            <div class="footer-links">
                <a href="<?php echo $lang_prefix; ?>/aviso-legal.html"><?php echo $ft['legal_notice']; ?></a>
                <a href="<?php echo $lang_prefix; ?>/politica-cookies.html"><?php echo $ft['cookies']; ?></a>
                <a href="<?php echo $lang_prefix; ?>/agradecimientos.html"><?php echo $ft['acknowledgments']; ?></a>
            </div>
            <div class="footer-social">
                <a href="https://www.facebook.com/rutasrurales.io" target="_blank" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="https://www.instagram.com/rutas_rurales/" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://twitter.com/rutasrurales" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        <div class="footer-copyright">
            <p><?php echo $ft['copyright']; ?></p>
        </div>
    </div>
</footer>

<style>
.footer {
    background-color: #2F5233;
    color: white;
    padding: 30px 15px;
    font-family: 'Montserrat', sans-serif, system-ui;
}
.footer .container { max-width: 1200px; margin: 0 auto; }
.footer-content-simple {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}
.footer-info, .footer-links, .footer-social {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
}
.footer-info span, .footer-links a, .footer-copyright p {
    font-size: 0.85rem;
    color: white;
    text-decoration: none;
}
.footer-info i { color: #d4a574; }
.footer-social a {
    color: white;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}
.footer-social a:hover { transform: translateY(-2px); color: #d4a574; }
.footer-copyright {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 15px;
    text-align: center;
    opacity: 0.7;
}
@media (max-width: 768px) {
    .footer-content-simple { flex-direction: column; text-align: center; }
    .footer-info, .footer-links { flex-direction: column; gap: 10px; }
}
</style>

<script src="script.js?v=20260114"></script>

</body>
</html>
