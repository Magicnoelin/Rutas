-- ============================================================
-- CONCEPTOS DE PAGO ÚNICO — billing_concepts
-- Ejecutar en phpMyAdmin del servidor de producción
-- ============================================================
-- billing_type = 'one_time' → pago único, sin suscripción
-- ============================================================

INSERT INTO billing_concepts (code, concept_name, description, amount, billing_type, active)
VALUES
-- ☕ Invítame a un café
('CAFE_1',    'Invítame a un café',           'Apoyo puntual a la plataforma. ¡Gracias!',                                          1.50,  'one_time', 1),
('CAFE_2',    'Invítame a dos cafés',          'Doble apoyo puntual a la plataforma. ¡Muy amable!',                                  3.00,  'one_time', 1),

-- 🏪 Publicación de negocio (pago único)
('NEGOCIO_5', 'Publica tu negocio — Básico',  'Publicación de página de negocio en Rutas Rurales. Visibilidad durante 1 año.',      5.00,  'one_time', 1),
('NEGOCIO_10','Publica tu negocio — Estándar','Publicación destacada de negocio. Incluye galería de fotos y posición preferente.',  10.00, 'one_time', 1),
('NEGOCIO_20','Publica tu negocio — Premium', 'Publicación premium con banner, galería ilimitada y posición top durante 1 año.',    20.00, 'one_time', 1),

-- 🎯 Apoyo a la plataforma (donación libre)
('APOYO_5',   'Apoya la plataforma — 5€',     'Contribución voluntaria para mantener y mejorar Rutas Rurales.',                     5.00,  'one_time', 1),
('APOYO_10',  'Apoya la plataforma — 10€',    'Contribución voluntaria para mantener y mejorar Rutas Rurales.',                    10.00,  'one_time', 1),
('APOYO_20',  'Apoya la plataforma — 20€',    'Contribución voluntaria para mantener y mejorar Rutas Rurales.',                    20.00,  'one_time', 1),
('APOYO_50',  'Apoya la plataforma — 50€',    'Gran contribución voluntaria. ¡Eres un héroe del turismo rural!',                   50.00,  'one_time', 1),

-- 📸 Servicios adicionales
('FOTO_EXTRA','Fotos adicionales',             'Pack de 10 fotos extra para tu alojamiento o negocio.',                              3.00,  'one_time', 1),
('DEST_MES',  'Destacado 1 mes',               'Tu alojamiento o negocio en posición destacada durante 1 mes.',                     15.00,  'one_time', 1)

ON DUPLICATE KEY UPDATE
    concept_name = VALUES(concept_name),
    description  = VALUES(description),
    amount       = VALUES(amount),
    active       = 1;

-- Verificar
SELECT code, concept_name, amount, billing_type FROM billing_concepts ORDER BY billing_type, amount;
