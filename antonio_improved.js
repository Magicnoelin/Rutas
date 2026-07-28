/* ===============================================
   ANTONIO - EL EXPERTO LOCAL (Mobile-First v4)
   JavaScript mejorado con datos reales, fotos, rutas y flujo natural
   + Sistema multilingüe (es/en/fr/de/zh)
   =============================================== */

// ===============================================
// DETECCIÓN DE IDIOMA
// ===============================================
const ANTONIO_LANG = (document.documentElement.lang || 'es').substring(0, 2).toLowerCase();

// ===============================================
// TRADUCCIONES
// ===============================================
const ANTONIO_I18N = {
    es: {
        title: 'Antonio, el experto local',
        online: 'En línea',
        close_btn: 'Cerrar panel',
        open_btn: 'Abrir asistente Antonio',
        btn_stays: 'Alojamientos',
        btn_places: 'Lugares',
        btn_activities: 'Actividades',
        btn_events: 'Eventos',
        btn_routes: 'Rutas',
        placeholder: 'Pregúntame sobre alojamientos, rutas, lugares...',
        send_btn: 'Enviar mensaje',
        footer_tip: '💡 Información verificada de nuestras bases de datos',
        cat_accommodations: 'Alojamientos',
        cat_places: 'Lugares de interés',
        cat_activities: 'Actividades',
        cat_events: 'Eventos culturales',
        cat_routes: 'Rutas temáticas',
        welcome_intro: `<p>¡Hola! Soy <strong>Antonio</strong>, tu experto local de turismo 🌄</p>
            <p>Puedo ayudarte a descubrir:</p>
            <ul>
                <li>🏨 <strong>Alojamientos</strong> - Casas rurales, hoteles, apartamentos</li>
                <li>🏛️ <strong>Lugares de interés</strong> - Patrimonio, naturaleza, monumentos</li>
                <li>🥾 <strong>Actividades</strong> - Senderismo, rutas, experiencias</li>
                <li>🎭 <strong>Eventos culturales</strong> - Festivales, conciertos</li>
                <li>🗺️ <strong>Rutas temáticas</strong> - Itinerarios completos</li>
            </ul>
            <p>¿Qué te apetece explorar? Puedes preguntarme directamente o usar los botones de abajo 👇</p>`,
        province_intro: `<p>Por cierto, si me dices qué provincia te interesa, puedo filtrarte mejor los resultados 😊</p>
            <p>Pero si prefieres, podemos explorar <strong>todo lo disponible</strong> sin filtrar.</p>`,
        province_label: '¿Buscas algo en alguna provincia?',
        see_all_btn: '🌍 Ver todo sin filtrar',
        see_all_provinces: '📋 Ver todas las provincias',
        all_provinces_title: 'Todas las provincias:',
        selected_province: (p) => `<p>¡Perfecto! Has seleccionado <strong>${p}</strong> 🗺️</p>
            <p>Ahora puedo mostrarte información específica para esta provincia.</p>
            <p>¿Qué te gustaría explorar en ${p}?</p>`,
        options_for: (p) => `Opciones para ${p}:`,
        all_provinces_msg: `<p>¡Perfecto! Te muestro información de <strong>todas las provincias</strong> 🌍</p>
            <p>¿Qué te gustaría ver? Pregúntame o elige una opción rápida 👇</p>`,
        greeting: `<p>¡Hola de nuevo! 😊 ¿En qué puedo ayudarte hoy?</p>
            <p>Recuerda que tengo información sobre:</p>
            <ul>
                <li>🏨 Alojamientos turísticos</li>
                <li>🏛️ Lugares de interés</li>
                <li>🥾 Actividades turísticas</li>
                <li>🎭 Eventos culturales</li>
                <li>🗺️ Rutas temáticas</li>
            </ul>
            <p>¿Qué te interesa explorar?</p>`,
        updating: (cat) => `<p>¡Qué buena elección! Estoy actualizando mi guía de ${cat} ✍️</p><p>Mientras termino, puedes echar un vistazo aquí:</p>`,
        explore_now: (cat) => `✨ Explorar ${cat} ahora`,
        no_results: (cat, prov) => `<p>No tengo ${cat} registrados${prov ? ' en ' + prov : ''} actualmente.</p>`,
        see_other_zones: `<p>¿Te gustaría ver los que tengo disponibles en otras zonas?</p>`,
        see_all_of: (cat) => `Ver todos los ${cat}`,
        showing: (cat, prov) => `Te muestro los <strong>${cat}</strong>${prov ? ' en <strong>' + prov + '</strong>' : ' disponibles'}`,
        also_interest: '💡 También te puede interesar:',
        no_info: (cat) => `<p>No tengo información disponible sobre eso en ${cat}.</p>`,
        results_found: (n, cat) => `<p>Encontré <strong>${n} resultado(s)</strong> en ${cat}:</p>`,
        not_permitted: `<p>Lo siento, solo puedo responder basándome en nuestras <strong>bases de datos internas</strong>:</p>`,
        suggest: 'Te sugiero:',
        try_with: 'Prueba con:',
        province_interest: (p) => `<p>¡Estupendo! Te interesa <strong>${p}</strong> 🗺️</p><p>¿Qué te gustaría conocer de ${p}? Puedo ayudarte con:</p>`,
        choose_option: 'Elige una opción:',
        stays_in: (p) => `🏨 Alojamientos en ${p}`,
        places_in: (p) => `🏛️ Lugares en ${p}`,
        activities_in: (p) => `🥾 Actividades en ${p}`,
        events_in: (p) => `🎭 Eventos en ${p}`,
        routes_in: (p) => `🗺️ Rutas en ${p}`,
        help_msg: `<p>¡Claro! Soy <strong>Antonio</strong>, tu guía local 🤠</p>
            <p>Puedo ayudarte con:</p>
            <ul>
                <li>🏨 <strong>Alojamientos</strong> - Casas rurales, hoteles, apartamentos</li>
                <li>🏛️ <strong>Lugares de interés</strong> - Monumentos, naturaleza, patrimonio</li>
                <li>🥾 <strong>Actividades</strong> - Senderismo, experiencias, aventura</li>
                <li>🎭 <strong>Eventos culturales</strong> - Festivales, conciertos, teatro</li>
                <li>🗺️ <strong>Rutas temáticas</strong> - Itinerarios completos de viaje</li>
            </ul>
            <p>Puedes preguntarme cosas como:</p>
            <ul>
                <li>"<em>¿Qué alojamientos hay en Soria?</em>"</li>
                <li>"<em>Muéstrame actividades de senderismo</em>"</li>
                <li>"<em>¿Hay eventos culturales este mes?</em>"</li>
                <li>"<em>Qué rutas temáticas tenéis</em>"</li>
            </ul>
            <p>¡Pregúntame lo que quieras! 😊</p>`,
        unknown_msg: `<p>No estoy seguro de haber entendido bien tu pregunta 🤔</p>
            <p>Recuerda que puedo ayudarte con información sobre:</p>
            <ul>
                <li>🏨 Alojamientos turísticos</li>
                <li>🏛️ Lugares de interés</li>
                <li>🥾 Actividades turísticas</li>
                <li>🎭 Eventos culturales</li>
                <li>🗺️ Rutas temáticas</li>
            </ul>
            <p>¿Por qué no pruebas a preguntarme de otra forma? 👇</p>`,
        not_found: (term) => `<p>No encontré nada sobre "<strong>${term}</strong>" en mi base de datos 🤔</p><p>¿Quieres probar con otra palabra o explorar alguna categoría?</p>`,
        found_results: (n, term) => `<p>He encontrado <strong>${n} resultado(s)</strong> sobre "<strong>${term}</strong>" 🔍</p>`,
        no_results_search: (cat, prov) => `<p>No encontré resultados para tu búsqueda en ${cat}${prov ? ' en ' + prov : ''}.</p>`,
        see_more: 'Ver más',
        verified_info: '💡 Información verificada de nuestras bases de datos',
    },
    en: {
        title: 'Antonio, the local expert',
        online: 'Online',
        close_btn: 'Close panel',
        open_btn: 'Open Antonio assistant',
        btn_stays: 'Accommodations',
        btn_places: 'Places',
        btn_activities: 'Activities',
        btn_events: 'Events',
        btn_routes: 'Routes',
        placeholder: 'Ask me about accommodations, routes, places...',
        send_btn: 'Send message',
        footer_tip: '💡 Verified information from our databases',
        cat_accommodations: 'Accommodations',
        cat_places: 'Places of interest',
        cat_activities: 'Activities',
        cat_events: 'Cultural events',
        cat_routes: 'Themed routes',
        welcome_intro: `<p>Hello! I'm <strong>Antonio</strong>, your local tourism expert 🌄</p>
            <p>I can help you discover:</p>
            <ul>
                <li>🏨 <strong>Accommodations</strong> - Rural houses, hotels, apartments</li>
                <li>🏛️ <strong>Places of interest</strong> - Heritage, nature, monuments</li>
                <li>🥾 <strong>Activities</strong> - Hiking, routes, experiences</li>
                <li>🎭 <strong>Cultural events</strong> - Festivals, concerts</li>
                <li>🗺️ <strong>Themed routes</strong> - Complete itineraries</li>
            </ul>
            <p>What would you like to explore? Ask me directly or use the buttons below 👇</p>`,
        province_intro: `<p>By the way, if you tell me which province interests you, I can filter results better 😊</p>
            <p>Or if you prefer, we can explore <strong>everything available</strong> without filtering.</p>`,
        province_label: 'Are you looking for something in a specific province?',
        see_all_btn: '🌍 See everything without filtering',
        see_all_provinces: '📋 See all provinces',
        all_provinces_title: 'All provinces:',
        selected_province: (p) => `<p>Great! You've selected <strong>${p}</strong> 🗺️</p>
            <p>Now I can show you specific information for this province.</p>
            <p>What would you like to explore in ${p}?</p>`,
        options_for: (p) => `Options for ${p}:`,
        all_provinces_msg: `<p>Perfect! I'll show you information from <strong>all provinces</strong> 🌍</p>
            <p>What would you like to see? Ask me or choose a quick option 👇</p>`,
        greeting: `<p>Hello again! 😊 How can I help you today?</p>
            <p>Remember I have information about:</p>
            <ul>
                <li>🏨 Tourist accommodations</li>
                <li>🏛️ Places of interest</li>
                <li>🥾 Tourist activities</li>
                <li>🎭 Cultural events</li>
                <li>🗺️ Themed routes</li>
            </ul>
            <p>What are you interested in exploring?</p>`,
        updating: (cat) => `<p>Great choice! I'm updating my guide on ${cat} ✍️</p><p>In the meantime, you can take a look here:</p>`,
        explore_now: (cat) => `✨ Explore ${cat} now`,
        no_results: (cat, prov) => `<p>I don't have any ${cat} registered${prov ? ' in ' + prov : ''} currently.</p>`,
        see_other_zones: `<p>Would you like to see what's available in other areas?</p>`,
        see_all_of: (cat) => `See all ${cat}`,
        showing: (cat, prov) => `Showing <strong>${cat}</strong>${prov ? ' in <strong>' + prov + '</strong>' : ' available'}`,
        also_interest: '💡 You might also be interested in:',
        no_info: (cat) => `<p>I don't have available information about that in ${cat}.</p>`,
        results_found: (n, cat) => `<p>Found <strong>${n} result(s)</strong> in ${cat}:</p>`,
        not_permitted: `<p>Sorry, I can only answer based on our <strong>internal databases</strong>:</p>`,
        suggest: 'I suggest:',
        try_with: 'Try with:',
        province_interest: (p) => `<p>Great! You're interested in <strong>${p}</strong> 🗺️</p><p>What would you like to know about ${p}? I can help you with:</p>`,
        choose_option: 'Choose an option:',
        stays_in: (p) => `🏨 Accommodations in ${p}`,
        places_in: (p) => `🏛️ Places in ${p}`,
        activities_in: (p) => `🥾 Activities in ${p}`,
        events_in: (p) => `🎭 Events in ${p}`,
        routes_in: (p) => `🗺️ Routes in ${p}`,
        help_msg: `<p>Of course! I'm <strong>Antonio</strong>, your local guide 🤠</p>
            <p>I can help you with:</p>
            <ul>
                <li>🏨 <strong>Accommodations</strong> - Rural houses, hotels, apartments</li>
                <li>🏛️ <strong>Places of interest</strong> - Monuments, nature, heritage</li>
                <li>🥾 <strong>Activities</strong> - Hiking, experiences, adventure</li>
                <li>🎭 <strong>Cultural events</strong> - Festivals, concerts, theatre</li>
                <li>🗺️ <strong>Themed routes</strong> - Complete travel itineraries</li>
            </ul>
            <p>You can ask me things like:</p>
            <ul>
                <li>"<em>What accommodations are there in Soria?</em>"</li>
                <li>"<em>Show me hiking activities</em>"</li>
                <li>"<em>Are there cultural events this month?</em>"</li>
                <li>"<em>What themed routes do you have?</em>"</li>
            </ul>
            <p>Ask me anything! 😊</p>`,
        unknown_msg: `<p>I'm not sure I understood your question correctly 🤔</p>
            <p>Remember I can help you with information about:</p>
            <ul>
                <li>🏨 Tourist accommodations</li>
                <li>🏛️ Places of interest</li>
                <li>🥾 Tourist activities</li>
                <li>🎭 Cultural events</li>
                <li>🗺️ Themed routes</li>
            </ul>
            <p>Why not try asking in a different way? 👇</p>`,
        not_found: (term) => `<p>I found nothing about "<strong>${term}</strong>" in my database 🤔</p><p>Would you like to try another word or explore a category?</p>`,
        found_results: (n, term) => `<p>I found <strong>${n} result(s)</strong> about "<strong>${term}</strong>" 🔍</p>`,
        no_results_search: (cat, prov) => `<p>No results found for your search in ${cat}${prov ? ' in ' + prov : ''}.</p>`,
        see_more: 'See more',
        verified_info: '💡 Verified information from our databases',
    },
    fr: {
        title: 'Antonio, l\'expert local',
        online: 'En ligne',
        close_btn: 'Fermer le panneau',
        open_btn: 'Ouvrir l\'assistant Antonio',
        btn_stays: 'Hébergements',
        btn_places: 'Lieux',
        btn_activities: 'Activités',
        btn_events: 'Événements',
        btn_routes: 'Itinéraires',
        placeholder: 'Posez-moi des questions sur les hébergements, les itinéraires, les lieux...',
        send_btn: 'Envoyer le message',
        footer_tip: '💡 Informations vérifiées de nos bases de données',
        cat_accommodations: 'Hébergements',
        cat_places: 'Lieux d\'intérêt',
        cat_activities: 'Activités',
        cat_events: 'Événements culturels',
        cat_routes: 'Itinéraires thématiques',
        welcome_intro: `<p>Bonjour ! Je suis <strong>Antonio</strong>, votre expert local en tourisme 🌄</p>
            <p>Je peux vous aider à découvrir :</p>
            <ul>
                <li>🏨 <strong>Hébergements</strong> - Maisons rurales, hôtels, appartements</li>
                <li>🏛️ <strong>Lieux d'intérêt</strong> - Patrimoine, nature, monuments</li>
                <li>🥾 <strong>Activités</strong> - Randonnée, circuits, expériences</li>
                <li>🎭 <strong>Événements culturels</strong> - Festivals, concerts</li>
                <li>🗺️ <strong>Itinéraires thématiques</strong> - Circuits complets</li>
            </ul>
            <p>Que souhaitez-vous explorer ? Posez-moi une question directement ou utilisez les boutons ci-dessous 👇</p>`,
        province_intro: `<p>Au fait, si vous me dites quelle province vous intéresse, je peux mieux filtrer les résultats 😊</p>
            <p>Ou si vous préférez, nous pouvons explorer <strong>tout ce qui est disponible</strong> sans filtrer.</p>`,
        province_label: 'Vous cherchez quelque chose dans une province particulière ?',
        see_all_btn: '🌍 Voir tout sans filtrer',
        see_all_provinces: '📋 Voir toutes les provinces',
        all_provinces_title: 'Toutes les provinces :',
        selected_province: (p) => `<p>Parfait ! Vous avez sélectionné <strong>${p}</strong> 🗺️</p>
            <p>Je peux maintenant vous montrer des informations spécifiques à cette province.</p>
            <p>Que souhaitez-vous explorer à ${p} ?</p>`,
        options_for: (p) => `Options pour ${p} :`,
        all_provinces_msg: `<p>Parfait ! Je vous montre des informations de <strong>toutes les provinces</strong> 🌍</p>
            <p>Que souhaitez-vous voir ? Posez-moi une question ou choisissez une option rapide 👇</p>`,
        greeting: `<p>Bonjour encore ! 😊 Comment puis-je vous aider aujourd'hui ?</p>
            <p>Rappel, j'ai des informations sur :</p>
            <ul>
                <li>🏨 Hébergements touristiques</li>
                <li>🏛️ Lieux d'intérêt</li>
                <li>🥾 Activités touristiques</li>
                <li>🎭 Événements culturels</li>
                <li>🗺️ Itinéraires thématiques</li>
            </ul>
            <p>Qu'est-ce qui vous intéresse ?</p>`,
        updating: (cat) => `<p>Excellent choix ! Je mets à jour mon guide sur ${cat} ✍️</p><p>En attendant, vous pouvez jeter un coup d'œil ici :</p>`,
        explore_now: (cat) => `✨ Explorer ${cat} maintenant`,
        no_results: (cat, prov) => `<p>Je n'ai pas de ${cat} enregistrés${prov ? ' à ' + prov : ''} actuellement.</p>`,
        see_other_zones: `<p>Souhaitez-vous voir ce qui est disponible dans d'autres zones ?</p>`,
        see_all_of: (cat) => `Voir tous les ${cat}`,
        showing: (cat, prov) => `Voici les <strong>${cat}</strong>${prov ? ' à <strong>' + prov + '</strong>' : ' disponibles'}`,
        also_interest: '💡 Vous pourriez aussi être intéressé par :',
        no_info: (cat) => `<p>Je n'ai pas d'informations disponibles à ce sujet dans ${cat}.</p>`,
        results_found: (n, cat) => `<p>Trouvé <strong>${n} résultat(s)</strong> dans ${cat} :</p>`,
        not_permitted: `<p>Désolé, je ne peux répondre qu'en me basant sur nos <strong>bases de données internes</strong> :</p>`,
        suggest: 'Je vous suggère :',
        try_with: 'Essayez avec :',
        province_interest: (p) => `<p>Excellent ! Vous êtes intéressé par <strong>${p}</strong> 🗺️</p><p>Que souhaitez-vous savoir sur ${p} ? Je peux vous aider avec :</p>`,
        choose_option: 'Choisissez une option :',
        stays_in: (p) => `🏨 Hébergements à ${p}`,
        places_in: (p) => `🏛️ Lieux à ${p}`,
        activities_in: (p) => `🥾 Activités à ${p}`,
        events_in: (p) => `🎭 Événements à ${p}`,
        routes_in: (p) => `🗺️ Itinéraires à ${p}`,
        help_msg: `<p>Bien sûr ! Je suis <strong>Antonio</strong>, votre guide local 🤠</p>
            <p>Je peux vous aider avec :</p>
            <ul>
                <li>🏨 <strong>Hébergements</strong> - Maisons rurales, hôtels, appartements</li>
                <li>🏛️ <strong>Lieux d'intérêt</strong> - Monuments, nature, patrimoine</li>
                <li>🥾 <strong>Activités</strong> - Randonnée, expériences, aventure</li>
                <li>🎭 <strong>Événements culturels</strong> - Festivals, concerts, théâtre</li>
                <li>🗺️ <strong>Itinéraires thématiques</strong> - Circuits de voyage complets</li>
            </ul>
            <p>Vous pouvez me poser des questions comme :</p>
            <ul>
                <li>"<em>Quels hébergements y a-t-il à Soria ?</em>"</li>
                <li>"<em>Montrez-moi des activités de randonnée</em>"</li>
                <li>"<em>Y a-t-il des événements culturels ce mois-ci ?</em>"</li>
                <li>"<em>Quels itinéraires thématiques avez-vous ?</em>"</li>
            </ul>
            <p>Posez-moi toutes vos questions ! 😊</p>`,
        unknown_msg: `<p>Je ne suis pas sûr d'avoir bien compris votre question 🤔</p>
            <p>Rappelons que je peux vous aider avec des informations sur :</p>
            <ul>
                <li>🏨 Hébergements touristiques</li>
                <li>🏛️ Lieux d'intérêt</li>
                <li>🥾 Activités touristiques</li>
                <li>🎭 Événements culturels</li>
                <li>🗺️ Itinéraires thématiques</li>
            </ul>
            <p>Pourquoi ne pas essayer de formuler votre question différemment ? 👇</p>`,
        not_found: (term) => `<p>Je n'ai rien trouvé sur "<strong>${term}</strong>" dans ma base de données 🤔</p><p>Voulez-vous essayer un autre mot ou explorer une catégorie ?</p>`,
        found_results: (n, term) => `<p>J'ai trouvé <strong>${n} résultat(s)</strong> sur "<strong>${term}</strong>" 🔍</p>`,
        no_results_search: (cat, prov) => `<p>Aucun résultat trouvé pour votre recherche dans ${cat}${prov ? ' à ' + prov : ''}.</p>`,
        see_more: 'Voir plus',
        verified_info: '💡 Informations vérifiées de nos bases de données',
    },
    de: {
        title: 'Antonio, der lokale Experte',
        online: 'Online',
        close_btn: 'Panel schließen',
        open_btn: 'Antonio-Assistenten öffnen',
        btn_stays: 'Unterkünfte',
        btn_places: 'Orte',
        btn_activities: 'Aktivitäten',
        btn_events: 'Veranstaltungen',
        btn_routes: 'Routen',
        placeholder: 'Fragen Sie mich nach Unterkünften, Routen, Orten...',
        send_btn: 'Nachricht senden',
        footer_tip: '💡 Verifizierte Informationen aus unseren Datenbanken',
        cat_accommodations: 'Unterkünfte',
        cat_places: 'Sehenswürdigkeiten',
        cat_activities: 'Aktivitäten',
        cat_events: 'Kulturelle Veranstaltungen',
        cat_routes: 'Themenrouten',
        welcome_intro: `<p>Hallo! Ich bin <strong>Antonio</strong>, Ihr lokaler Tourismusexperte 🌄</p>
            <p>Ich kann Ihnen helfen, Folgendes zu entdecken:</p>
            <ul>
                <li>🏨 <strong>Unterkünfte</strong> - Landhäuser, Hotels, Apartments</li>
                <li>🏛️ <strong>Sehenswürdigkeiten</strong> - Kulturerbe, Natur, Denkmäler</li>
                <li>🥾 <strong>Aktivitäten</strong> - Wandern, Touren, Erlebnisse</li>
                <li>🎭 <strong>Kulturelle Veranstaltungen</strong> - Festivals, Konzerte</li>
                <li>🗺️ <strong>Themenrouten</strong> - Vollständige Reiserouten</li>
            </ul>
            <p>Was möchten Sie erkunden? Fragen Sie mich direkt oder nutzen Sie die Schaltflächen unten 👇</p>`,
        province_intro: `<p>Übrigens, wenn Sie mir sagen, welche Provinz Sie interessiert, kann ich die Ergebnisse besser filtern 😊</p>
            <p>Oder wenn Sie möchten, können wir <strong>alles Verfügbare</strong> ohne Filter erkunden.</p>`,
        province_label: 'Suchen Sie etwas in einer bestimmten Provinz?',
        see_all_btn: '🌍 Alles ohne Filter anzeigen',
        see_all_provinces: '📋 Alle Provinzen anzeigen',
        all_provinces_title: 'Alle Provinzen:',
        selected_province: (p) => `<p>Perfekt! Sie haben <strong>${p}</strong> ausgewählt 🗺️</p>
            <p>Jetzt kann ich Ihnen spezifische Informationen für diese Provinz zeigen.</p>
            <p>Was möchten Sie in ${p} erkunden?</p>`,
        options_for: (p) => `Optionen für ${p}:`,
        all_provinces_msg: `<p>Perfekt! Ich zeige Ihnen Informationen aus <strong>allen Provinzen</strong> 🌍</p>
            <p>Was möchten Sie sehen? Fragen Sie mich oder wählen Sie eine schnelle Option 👇</p>`,
        greeting: `<p>Hallo nochmal! 😊 Wie kann ich Ihnen heute helfen?</p>
            <p>Denken Sie daran, ich habe Informationen über:</p>
            <ul>
                <li>🏨 Touristische Unterkünfte</li>
                <li>🏛️ Sehenswürdigkeiten</li>
                <li>🥾 Touristische Aktivitäten</li>
                <li>🎭 Kulturelle Veranstaltungen</li>
                <li>🗺️ Themenrouten</li>
            </ul>
            <p>Was interessiert Sie?</p>`,
        updating: (cat) => `<p>Ausgezeichnete Wahl! Ich aktualisiere meinen Leitfaden zu ${cat} ✍️</p><p>In der Zwischenzeit können Sie hier nachsehen:</p>`,
        explore_now: (cat) => `✨ ${cat} jetzt erkunden`,
        no_results: (cat, prov) => `<p>Ich habe derzeit keine ${cat}${prov ? ' in ' + prov : ''} registriert.</p>`,
        see_other_zones: `<p>Möchten Sie sehen, was in anderen Gebieten verfügbar ist?</p>`,
        see_all_of: (cat) => `Alle ${cat} anzeigen`,
        showing: (cat, prov) => `Ich zeige Ihnen die <strong>${cat}</strong>${prov ? ' in <strong>' + prov + '</strong>' : ' verfügbar'}`,
        also_interest: '💡 Das könnte Sie auch interessieren:',
        no_info: (cat) => `<p>Ich habe keine verfügbaren Informationen dazu in ${cat}.</p>`,
        results_found: (n, cat) => `<p><strong>${n} Ergebnis(se)</strong> in ${cat} gefunden:</p>`,
        not_permitted: `<p>Entschuldigung, ich kann nur anhand unserer <strong>internen Datenbanken</strong> antworten:</p>`,
        suggest: 'Ich schlage vor:',
        try_with: 'Versuchen Sie mit:',
        province_interest: (p) => `<p>Toll! Sie interessieren sich für <strong>${p}</strong> 🗺️</p><p>Was möchten Sie über ${p} wissen? Ich kann Ihnen helfen mit:</p>`,
        choose_option: 'Wählen Sie eine Option:',
        stays_in: (p) => `🏨 Unterkünfte in ${p}`,
        places_in: (p) => `🏛️ Orte in ${p}`,
        activities_in: (p) => `🥾 Aktivitäten in ${p}`,
        events_in: (p) => `🎭 Veranstaltungen in ${p}`,
        routes_in: (p) => `🗺️ Routen in ${p}`,
        help_msg: `<p>Natürlich! Ich bin <strong>Antonio</strong>, Ihr lokaler Reiseführer 🤠</p>
            <p>Ich kann Ihnen helfen mit:</p>
            <ul>
                <li>🏨 <strong>Unterkünfte</strong> - Landhäuser, Hotels, Apartments</li>
                <li>🏛️ <strong>Sehenswürdigkeiten</strong> - Denkmäler, Natur, Kulturerbe</li>
                <li>🥾 <strong>Aktivitäten</strong> - Wandern, Erlebnisse, Abenteuer</li>
                <li>🎭 <strong>Kulturelle Veranstaltungen</strong> - Festivals, Konzerte, Theater</li>
                <li>🗺️ <strong>Themenrouten</strong> - Vollständige Reiserouten</li>
            </ul>
            <p>Sie können mich Dinge fragen wie:</p>
            <ul>
                <li>"<em>Welche Unterkünfte gibt es in Soria?</em>"</li>
                <li>"<em>Zeigen Sie mir Wanderaktivitäten</em>"</li>
                <li>"<em>Gibt es diesen Monat kulturelle Veranstaltungen?</em>"</li>
                <li>"<em>Welche Themenrouten haben Sie?</em>"</li>
            </ul>
            <p>Fragen Sie mich alles! 😊</p>`,
        unknown_msg: `<p>Ich bin nicht sicher, ob ich Ihre Frage richtig verstanden habe 🤔</p>
            <p>Denken Sie daran, ich kann Ihnen mit Informationen über folgendes helfen:</p>
            <ul>
                <li>🏨 Touristische Unterkünfte</li>
                <li>🏛️ Sehenswürdigkeiten</li>
                <li>🥾 Touristische Aktivitäten</li>
                <li>🎭 Kulturelle Veranstaltungen</li>
                <li>🗺️ Themenrouten</li>
            </ul>
            <p>Warum versuchen Sie es nicht mit einer anderen Formulierung? 👇</p>`,
        not_found: (term) => `<p>Ich habe nichts über "<strong>${term}</strong>" in meiner Datenbank gefunden 🤔</p><p>Möchten Sie ein anderes Wort versuchen oder eine Kategorie erkunden?</p>`,
        found_results: (n, term) => `<p>Ich habe <strong>${n} Ergebnis(se)</strong> über "<strong>${term}</strong>" gefunden 🔍</p>`,
        no_results_search: (cat, prov) => `<p>Keine Ergebnisse für Ihre Suche in ${cat}${prov ? ' in ' + prov : ''} gefunden.</p>`,
        see_more: 'Mehr sehen',
        verified_info: '💡 Verifizierte Informationen aus unseren Datenbanken',
    },
    zh: {
        title: 'Antonio，当地专家',
        online: '在线',
        close_btn: '关闭面板',
        open_btn: '打开Antonio助手',
        btn_stays: '住宿',
        btn_places: '景点',
        btn_activities: '活动',
        btn_events: '活动',
        btn_routes: '路线',
        placeholder: '请询问住宿、路线、景点...',
        send_btn: '发送消息',
        footer_tip: '💡 来自我们数据库的经过验证的信息',
        cat_accommodations: '住宿',
        cat_places: '景点',
        cat_activities: '旅游活动',
        cat_events: '文化活动',
        cat_routes: '主题路线',
        welcome_intro: `<p>您好！我是<strong>Antonio</strong>，您的当地旅游专家 🌄</p>
            <p>我可以帮您发现：</p>
            <ul>
                <li>🏨 <strong>住宿</strong> - 农村民宿、酒店、公寓</li>
                <li>🏛️ <strong>景点</strong> - 文化遗产、自然风光、纪念碑</li>
                <li>🥾 <strong>活动</strong> - 徒步、路线、体验</li>
                <li>🎭 <strong>文化活动</strong> - 节日、音乐会</li>
                <li>🗺️ <strong>主题路线</strong> - 完整行程</li>
            </ul>
            <p>您想探索什么？直接问我或使用下面的按钮 👇</p>`,
        province_intro: `<p>顺便问一下，如果您告诉我您感兴趣的省份，我可以更好地过滤结果 😊</p>
            <p>或者如果您愿意，我们可以浏览<strong>所有可用内容</strong>而不过滤。</p>`,
        province_label: '您在某个特定省份寻找什么吗？',
        see_all_btn: '🌍 不过滤查看全部',
        see_all_provinces: '📋 查看所有省份',
        all_provinces_title: '所有省份：',
        selected_province: (p) => `<p>太好了！您选择了<strong>${p}</strong> 🗺️</p>
            <p>现在我可以向您展示这个省份的具体信息。</p>
            <p>您想在${p}探索什么？</p>`,
        options_for: (p) => `${p}的选项：`,
        all_provinces_msg: `<p>好的！我向您展示<strong>所有省份</strong>的信息 🌍</p>
            <p>您想看什么？请问我或选择一个快速选项 👇</p>`,
        greeting: `<p>您好！😊 今天我能帮您什么？</p>
            <p>请记住我有以下信息：</p>
            <ul>
                <li>🏨 旅游住宿</li>
                <li>🏛️ 景点</li>
                <li>🥾 旅游活动</li>
                <li>🎭 文化活动</li>
                <li>🗺️ 主题路线</li>
            </ul>
            <p>您对什么感兴趣？</p>`,
        updating: (cat) => `<p>好选择！我正在更新关于${cat}的指南 ✍️</p><p>与此同时，您可以在这里查看：</p>`,
        explore_now: (cat) => `✨ 立即探索${cat}`,
        no_results: (cat, prov) => `<p>目前我没有${prov ? prov + '的' : ''}${cat}记录。</p>`,
        see_other_zones: `<p>您想查看其他地区的内容吗？</p>`,
        see_all_of: (cat) => `查看所有${cat}`,
        showing: (cat, prov) => `为您展示${prov ? '<strong>' + prov + '</strong>的' : ''}可用<strong>${cat}</strong>`,
        also_interest: '💡 您可能也感兴趣：',
        no_info: (cat) => `<p>我没有关于${cat}的可用信息。</p>`,
        results_found: (n, cat) => `<p>在${cat}中找到<strong>${n}个结果</strong>：</p>`,
        not_permitted: `<p>抱歉，我只能根据我们的<strong>内部数据库</strong>回答：</p>`,
        suggest: '我建议：',
        try_with: '尝试：',
        province_interest: (p) => `<p>太好了！您对<strong>${p}</strong>感兴趣 🗺️</p><p>您想了解${p}的什么？我可以帮您：</p>`,
        choose_option: '选择一个选项：',
        stays_in: (p) => `🏨 ${p}的住宿`,
        places_in: (p) => `🏛️ ${p}的景点`,
        activities_in: (p) => `🥾 ${p}的活动`,
        events_in: (p) => `🎭 ${p}的活动`,
        routes_in: (p) => `🗺️ ${p}的路线`,
        help_msg: `<p>当然！我是<strong>Antonio</strong>，您的当地导游 🤠</p>
            <p>我可以帮您：</p>
            <ul>
                <li>🏨 <strong>住宿</strong> - 农村民宿、酒店、公寓</li>
                <li>🏛️ <strong>景点</strong> - 纪念碑、自然风光、文化遗产</li>
                <li>🥾 <strong>活动</strong> - 徒步、体验、冒险</li>
                <li>🎭 <strong>文化活动</strong> - 节日、音乐会、剧院</li>
                <li>🗺️ <strong>主题路线</strong> - 完整旅行行程</li>
            </ul>
            <p>您可以问我这样的问题：</p>
            <ul>
                <li>"<em>索里亚有哪些住宿？</em>"</li>
                <li>"<em>给我看徒步活动</em>"</li>
                <li>"<em>本月有文化活动吗？</em>"</li>
                <li>"<em>你们有哪些主题路线？</em>"</li>
            </ul>
            <p>随时问我！😊</p>`,
        unknown_msg: `<p>我不确定是否正确理解了您的问题 🤔</p>
            <p>请记住我可以帮您提供以下信息：</p>
            <ul>
                <li>🏨 旅游住宿</li>
                <li>🏛️ 景点</li>
                <li>🥾 旅游活动</li>
                <li>🎭 文化活动</li>
                <li>🗺️ 主题路线</li>
            </ul>
            <p>为什么不换种方式提问试试？ 👇</p>`,
        not_found: (term) => `<p>我在数据库中没有找到关于"<strong>${term}</strong>"的内容 🤔</p><p>您想尝试其他词汇或探索某个类别吗？</p>`,
        found_results: (n, term) => `<p>我找到了关于"<strong>${term}</strong>"的<strong>${n}个结果</strong> 🔍</p>`,
        no_results_search: (cat, prov) => `<p>在${cat}${prov ? '的' + prov : ''}中没有找到搜索结果。</p>`,
        see_more: '查看更多',
        verified_info: '💡 来自我们数据库的经过验证的信息',
    }
};

// Obtener las traducciones del idioma activo (fallback a español)
const i18n = ANTONIO_I18N[ANTONIO_LANG] || ANTONIO_I18N['es'];

// ESTADO DE LA CONVERSACIÓN
let antonioState = {
    provincia: null,
    dias: null,
    intereses: [],
    presupuesto: null,
    alojamiento: null,
    temporada: null,
    historial: [],
    fuentesPermitidas: ['accommodations', 'places_of_interest', 'tourist_activities', 'cultural_events', 'routes'],
    categorias: {
        'accommodations':     { nombre: i18n.cat_accommodations, icono: '🏨', color: '#2c5f2d' },
        'places_of_interest': { nombre: i18n.cat_places,         icono: '🏛️', color: '#4a8c4b' },
        'tourist_activities': { nombre: i18n.cat_activities,     icono: '🥾', color: '#d4a574' },
        'cultural_events':    { nombre: i18n.cat_events,         icono: '🎭', color: '#e74c3c' },
        'routes':             { nombre: i18n.cat_routes,         icono: '🗺️', color: '#8e44ad' }
    }
};

// BASE DE DATOS - Se llena con datos reales desde la API
const antonioDatabase = {
    accommodations: [],
    places_of_interest: [],
    tourist_activities: [],
    cultural_events: [],
    routes: []
};

// Provincias más relevantes (ordenadas por popularidad turística)
const provinciasPopulares = [
    'Soria', 'Ávila', 'Segovia', 'Burgos', 'León', 'Asturias', 'Cantabria',
    'Huesca', 'Gerona', 'Lérida', 'Navarra', 'La Rioja', 'Zamora', 'Salamanca',
    'Cáceres', 'Toledo', 'Cuenca', 'Teruel', 'Jaén', 'Granada', 'Málaga'
];

// ===============================================
// INICIALIZACIÓN
// ===============================================

document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('antonio-widget')) {
        crearWidgetAntonio();
    }
    configurarEventos();
    cargarDatosReales();
    setTimeout(() => {
        mostrarMensajeBienvenida();
    }, 1200);
});

function crearWidgetAntonio() {
    const widgetHTML = `
        <div id="antonio-widget">
            <button id="antonio-fab" aria-label="${i18n.open_btn}">
                <img src="/antonio.jpg" alt="Antonio - ${i18n.title}" onerror="this.src='/favicon.png'">
                <span class="antonio-badge" id="antonio-badge">1</span>
            </button>
            <div id="antonio-panel">
                <div id="antonio-header">
                    <div class="antonio-header-info">
                        <img src="/antonio.jpg" alt="Antonio" onerror="this.src='/favicon.png'">
                        <div>
                            <strong>${i18n.title}</strong>
                            <div class="antonio-status">
                                <span class="antonio-dot"></span>
                                <span>${i18n.online}</span>
                            </div>
                        </div>
                    </div>
                    <button id="antonio-close" aria-label="${i18n.close_btn}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="antonio-messages"></div>
                <div id="antonio-quick-options">
                    <button class="antonio-quick-btn" data-option="alojamientos">
                        <i class="fas fa-bed"></i> ${i18n.btn_stays}
                    </button>
                    <button class="antonio-quick-btn" data-option="lugares">
                        <i class="fas fa-landmark"></i> ${i18n.btn_places}
                    </button>
                    <button class="antonio-quick-btn" data-option="actividades">
                        <i class="fas fa-hiking"></i> ${i18n.btn_activities}
                    </button>
                    <button class="antonio-quick-btn" data-option="eventos">
                        <i class="fas fa-calendar-alt"></i> ${i18n.btn_events}
                    </button>
                    <button class="antonio-quick-btn" data-option="rutas">
                        <i class="fas fa-route"></i> ${i18n.btn_routes}
                    </button>
                </div>
                <div id="antonio-input-area">
                    <input type="text" id="antonio-input" placeholder="${i18n.placeholder}" maxlength="500">
                    <button id="antonio-send" aria-label="${i18n.send_btn}">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div id="antonio-footer">
                    <p>${i18n.footer_tip}</p>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', widgetHTML);
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = '/antonio_improved.css';
    document.head.appendChild(link);
}

function configurarEventos() {
    document.getElementById('antonio-fab').addEventListener('click', togglePanel);
    document.getElementById('antonio-close').addEventListener('click', togglePanel);
    document.getElementById('antonio-send').addEventListener('click', enviarMensajeUsuario);
    document.getElementById('antonio-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') enviarMensajeUsuario();
    });
    document.querySelectorAll('.antonio-quick-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            manejarOpcionRapida(this.getAttribute('data-option'));
        });
    });
    document.addEventListener('click', function(event) {
        const panel = document.getElementById('antonio-panel');
        const fab = document.getElementById('antonio-fab');
        if (panel.classList.contains('antonio-open') &&
            !panel.contains(event.target) &&
            !fab.contains(event.target) &&
            window.innerWidth >= 769) {
            togglePanel();
        }
    });
}

// ===============================================
// CARGA DE DATOS REALES
// ===============================================

function cargarDatosReales() {
    fetch('/api/get_antonio_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.accommodations)     antonioDatabase.accommodations = data.accommodations;
            if (data.places_of_interest) antonioDatabase.places_of_interest = data.places_of_interest;
            if (data.tourist_activities) antonioDatabase.tourist_activities = data.tourist_activities;
            if (data.cultural_events)    antonioDatabase.cultural_events = data.cultural_events;
            if (data.routes)             antonioDatabase.routes = data.routes;
            console.log('✅ Antonio: Datos cargados correctamente');
        })
        .catch(error => {
            console.error('Error cargando datos de Antonio:', error);
            // Fallback: datos de ejemplo mínimos
            cargarDatosEjemplo();
        });
}

function cargarDatosEjemplo() {
    antonioDatabase.accommodations = [
        { id: 1, nombre: 'Posada Real de Soria', descripcion: 'Un remanso de paz con techos de madera.', ubicacion: 'Soria', province: 'Soria', precio: '85€/noche', icono: '🏨', url: '/alojamientos-turisticos.html', foto: '' },
        { id: 2, nombre: 'Casa Rural El Mirador', descripcion: 'Vistas increíbles, ideal para parejas.', ubicacion: 'Ávila', province: 'Ávila', precio: '70€/noche', icono: '🏠', url: '/alojamientos-turisticos.html', foto: '' },
        { id: 3, nombre: 'Casa Enrique', descripcion: 'Casa acogedora en plena naturaleza soriana.', ubicacion: 'Santervas de la Sierra', province: 'Soria', precio: 'Desde 90€/noche', icono: '🏔️', url: '/alojamientos-turisticos.html', foto: '' },
        { id: 4, nombre: 'La Plaza', descripcion: 'Apartamento ideal para parejas en Vinuesa.', ubicacion: 'Vinuesa', province: 'Soria', precio: 'Desde 75€/noche', icono: '💑', url: '/alojamientos-turisticos.html', foto: '' },
        { id: 5, nombre: 'Casa Chaparrete', descripcion: 'Casa rural tradicional con chimenea en Deza.', ubicacion: 'Deza', province: 'Soria', precio: 'Desde 80€/noche', icono: '🔥', url: '/alojamientos-turisticos.html', foto: '' }
    ];
    antonioDatabase.places_of_interest = [
        { id: 101, nombre: 'Cañón del Río Lobos', descripcion: 'Espectáculo natural de piedra y buitres.', ubicacion: 'Soria', province: 'Soria', precio: 'Gratis', icono: '🏞️', url: '/lugares-interes-paginacion.html', foto: '' },
        { id: 102, nombre: 'Monasterio de San Juan de Duero', descripcion: 'Joya del románico con claustro único.', ubicacion: 'Soria Capital', province: 'Soria', precio: 'Gratis', icono: '⛪', url: '/lugares-interes-paginacion.html', foto: '' },
        { id: 103, nombre: 'Pico Urbión', descripcion: 'Cima de Soria con lagunas glaciares.', ubicacion: 'Sistema Ibérico', province: 'Soria', precio: 'Gratis', icono: '🏔️', url: '/lugares-interes-paginacion.html', foto: '' },
        { id: 104, nombre: 'Castillo de Almenar', descripcion: 'Fortaleza medieval del siglo XIII.', ubicacion: 'Almenar de Soria', province: 'Soria', precio: 'Gratis', icono: '🏰', url: '/lugares-interes-paginacion.html', foto: '' }
    ];
    antonioDatabase.tourist_activities = [
        { id: 201, nombre: 'Ruta de las Estrellas', descripcion: 'Observación astronómica Starlight.', ubicacion: 'Soria', province: 'Soria', precio: '15€', icono: '⭐', url: '/actividades-turisticas.html', foto: '' },
        { id: 202, nombre: 'Gastronomía Soriana', descripcion: 'Torreznos, migas, setas y trufa negra.', ubicacion: 'Soria', province: 'Soria', precio: 'Consultar', icono: '🍖', url: '/actividades-turisticas.html', foto: '' }
    ];
    antonioDatabase.cultural_events = [
        { id: 301, nombre: 'Festival de las Ánimas', descripcion: 'Leyendas de Bécquer en Soria.', ubicacion: 'Soria', province: 'Soria', fecha: 'Noviembre', precio: '10€', icono: '🎭', url: '/eventos-culturales-paginacion.html', foto: '' }
    ];
    antonioDatabase.routes = [
        { id: 1, nombre: 'Puente 1 de Mayo en Soria', descripcion: 'Escapada de 3 días a Soria.', ubicacion: 'Soria', province: 'Soria', duracion: '3 días', icono: '🗺️', url: '/rutas/puente-1-mayo-soria', foto: '', color: '#2F5233' }
    ];
}

// ===============================================
// PANEL
// ===============================================

function togglePanel() {
    const panel = document.getElementById('antonio-panel');
    panel.classList.toggle('antonio-open');
    if (panel.classList.contains('antonio-open')) {
        document.getElementById('antonio-badge').style.display = 'none';
        setTimeout(() => document.getElementById('antonio-input').focus(), 300);
        setTimeout(() => {
            const messages = document.getElementById('antonio-messages');
            messages.scrollTop = messages.scrollHeight;
        }, 350);
    }
}

// ===============================================
// MENSAJES DE BIENVENIDA (FLUJO NATURAL)
// ===============================================

function mostrarMensajeBienvenida() {
    agregarMensajeBot(i18n.welcome_intro);

    // Preguntar provincia de forma natural, no obligatoria
    setTimeout(() => {
        preguntarProvinciaNatural();
    }, 800);
}

function preguntarProvinciaNatural() {
    let mensaje = i18n.province_intro;
    mensaje += `<div class="antonio-suggestions">
        <div class="antonio-suggestions-title">
            <i class="fas fa-map-marker-alt"></i> ${i18n.province_label}
        </div>
        <div class="antonio-suggestions-list">
            <button class="antonio-suggestion-btn" onclick="saltarProvincia()">
                ${i18n.see_all_btn}
            </button>
    `;

    // Mostrar solo las provincias más populares (no las 50)
    const provinciasSugeridas = provinciasPopulares.slice(0, 8);
    provinciasSugeridas.forEach(p => {
        mensaje += `<button class="antonio-suggestion-btn" onclick="seleccionarProvincia('${p}')">📍 ${p}</button>\n`;
    });

    mensaje += `
            </div>
            <div style="margin-top:8px;font-size:0.8rem;color:#888;">
                <button class="antonio-suggestion-btn" onclick="mostrarTodasProvincias()" style="font-size:0.8rem;">
                    ${i18n.see_all_provinces}
                </button>
            </div>
        </div>
    `;

    agregarMensajeBot(mensaje);
}

function saltarProvincia() {
    antonioState.provincia = null;
    agregarMensajeBot(i18n.all_provinces_msg);
}

function mostrarTodasProvincias() {
    const provinciasEspana = [
        'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
        'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ciudad Real', 'Córdoba', 'Cuenca',
        'Gerona', 'Granada', 'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares',
        'Jaén', 'La Coruña', 'La Rioja', 'Las Palmas', 'León', 'Lérida', 'Lugo', 'Madrid', 'Málaga',
        'Murcia', 'Navarra', 'Orense', 'Palencia', 'Pontevedra', 'Salamanca', 'Santa Cruz de Tenerife',
        'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Teruel', 'Toledo', 'Valencia', 'Valladolid',
        'Vizcaya', 'Zamora', 'Zaragoza'
    ];

    let selector = `<div class="antonio-suggestions">
        <div class="antonio-suggestions-title"><i class="fas fa-map-marker-alt"></i> ${i18n.all_provinces_title}</div>
        <div class="antonio-suggestions-list" style="max-height:250px;overflow-y:auto;">`;

    const porLetra = {};
    provinciasEspana.forEach(p => {
        const l = p.charAt(0).toUpperCase();
        if (!porLetra[l]) porLetra[l] = [];
        porLetra[l].push(p);
    });

    Object.keys(porLetra).sort().forEach(letra => {
        selector += `<div style="margin-bottom:6px;">
            <div style="font-size:0.75rem;color:#666;font-weight:600;">${letra}</div>
            <div style="display:flex;flex-wrap:wrap;gap:4px;">`;
        porLetra[letra].forEach(p => {
            selector += `<button class="antonio-suggestion-btn" onclick="seleccionarProvincia('${p}')" style="font-size:0.75rem;padding:4px 8px;">${p}</button>`;
        });
        selector += `</div></div>`;
    });

    selector += `</div></div>`;
    agregarMensajeBot(selector);
}

function seleccionarProvincia(provincia) {
    antonioState.provincia = provincia;
    agregarMensajeBot(i18n.selected_province(provincia));

    setTimeout(() => {
        agregarMensajeBot(`
            <div class="antonio-suggestions">
                <div class="antonio-suggestions-title"><i class="fas fa-bolt"></i> ${i18n.options_for(provincia)}</div>
                <div class="antonio-suggestions-list">
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">${i18n.stays_in(provincia)}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">${i18n.places_in(provincia)}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">${i18n.activities_in(provincia)}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">${i18n.events_in(provincia)}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">${i18n.routes_in(provincia)}</button>
                </div>
            </div>
        `);
    }, 500);
}

// ===============================================
// ENVÍO Y PROCESAMIENTO DE MENSAJES
// ===============================================

function enviarMensajeUsuario() {
    const input = document.getElementById('antonio-input');
    const mensaje = input.value.trim();
    if (!mensaje) return;
    agregarMensajeUsuario(mensaje);
    input.value = '';
    setTimeout(() => procesarMensajeUsuario(mensaje), 500);
}

function manejarOpcionRapida(opcion) {
    const mapOpciones = {
        'alojamientos': i18n.btn_stays,
        'lugares':      i18n.btn_places,
        'actividades':  i18n.btn_activities,
        'eventos':      i18n.btn_events,
        'rutas':        i18n.btn_routes
    };
    const mensaje = mapOpciones[opcion] || opcion;
    agregarMensajeUsuario(mensaje);
    procesarMensajeUsuario(mensaje);
}

function procesarMensajeUsuario(mensaje) {
    const mensajeLower = mensaje.toLowerCase();
    mostrarTypingIndicator();

    if (esPreguntaNoPermitida(mensajeLower)) {
        setTimeout(() => {
            ocultarTypingIndicator();
            redirigirPreguntaNoPermitida(mensajeLower);
        }, 1000);
        return;
    }

    const intencion = analizarIntencion(mensajeLower);

    setTimeout(() => {
        ocultarTypingIndicator();
        switch(intencion.tipo) {
            case 'saludo':          responderSaludo(); break;
            case 'categoria':       mostrarCategoria(intencion.categoria); break;
            case 'busqueda':        buscarEnCategoria(intencion.categoria, intencion.terminos); break;
            case 'busqueda_global': busquedaGlobal(intencion.termino); break;
            case 'provincia':       mostrarTodasProvincias(); break;
            case 'provincia_sola':  responderProvinciaSola(intencion.provincia); break;
            case 'ayuda':           mostrarAyuda(); break;
            case 'desconocido':     responderDesconocido(); break;
        }
    }, 1500);
}


function esPreguntaNoPermitida(mensaje) {
    const palabrasNoPermitidas = [
        'restaurante', 'bar', 'cafetería', 'comer', 'cenar', 'almorzar',
        'restaurant', 'eat', 'dinner', 'lunch', 'café',
        'tienda', 'compras', 'supermercado', 'mercado', 'shop', 'shopping',
        'transporte', 'autobús', 'tren', 'taxi', 'coche', 'autocar',
        'transport', 'bus', 'train',
        'hospital', 'farmacia', 'médico', 'urgencias', 'hospital', 'pharmacy',
        'policía', 'emergencias', 'bomberos', 'police', 'emergency',
        'banco', 'cajero', 'dinero', 'cambio', 'bank', 'money',
        'wifi', 'internet', 'conexión'
    ];
    return palabrasNoPermitidas.some(palabra => mensaje.includes(palabra));
}

function redirigirPreguntaNoPermitida(mensaje) {
    let respuesta = i18n.not_permitted;
    respuesta += `<div class="antonio-suggestions">
        <div class="antonio-suggestions-title"><i class="fas fa-lightbulb"></i> ${i18n.suggest}</div>
        <div class="antonio-suggestions-list">
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">🏨 ${i18n.btn_stays}</button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">🏛️ ${i18n.btn_places}</button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">🥾 ${i18n.btn_activities}</button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">🎭 ${i18n.btn_events}</button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">🗺️ ${i18n.btn_routes}</button>
        </div>
    </div>`;
    agregarMensajeBot(respuesta);
}

// Lista completa de provincias para detección en mensajes
const provinciasEspana = [
    'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
    'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ciudad Real', 'Córdoba', 'Cuenca',
    'Gerona', 'Granada', 'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares',
    'Jaén', 'La Coruña', 'La Rioja', 'Las Palmas', 'León', 'Lérida', 'Lugo', 'Madrid', 'Málaga',
    'Murcia', 'Navarra', 'Orense', 'Palencia', 'Pontevedra', 'Salamanca', 'Santa Cruz de Tenerife',
    'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Teruel', 'Toledo', 'Valencia', 'Valladolid',
    'Vizcaya', 'Zamora', 'Zaragoza'
];

// Provincias en minúscula para búsqueda
const provinciasLower = provinciasEspana.map(p => p.toLowerCase());

function detectarProvinciaEnMensaje(mensaje) {
    // Normalizar: quitar tildes para comparación
    const mensajeNorm = mensaje.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    for (let i = 0; i < provinciasLower.length; i++) {
        const provNorm = provinciasLower[i].normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        // Buscar la provincia como palabra completa (no parte de otra palabra)
        const regex = new RegExp('\\b' + provNorm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'i');
        if (regex.test(mensajeNorm)) {
            return provinciasEspana[i];
        }
    }
    return null;
}


function analizarIntencion(mensaje) {
    // 1. Detectar provincia mencionada en el mensaje
    const provinciaDetectada = detectarProvinciaEnMensaje(mensaje);
    if (provinciaDetectada) {
        antonioState.provincia = provinciaDetectada;
    }

    // 2. Saludos (ES + EN + FR + DE + ZH)
    if (mensaje.match(/hola|buenas|hey|hi|hello|qué tal|como estás|buenos días|buenas tardes|bonjour|bonsoir|salut|guten tag|hallo|你好|您好/)) {
        return { tipo: 'saludo' };
    }

    // 3. Detectar categoría (palabras clave en todos los idiomas)
    let categoria = null;

    // Rutas / Routes / Itinéraires / Routen / 路线
    if (mensaje.match(/ruta|itinerario|escapada|puente|viaje organizado|plan de viaje|route|itinerary|trip plan|itinéraire|voyage|reise|ausflug|路线|行程|旅游计划/)) {
        categoria = 'routes';
    }
    // Alojamientos / Accommodations / Hébergements / Unterkünfte / 住宿
    else if (mensaje.match(/alojamiento|hotel|casa rural|apartamento|dormir|pernoctar|hospedaje|accommodation|stay|lodging|hébergement|logement|unterkunft|übernachtung|住宿|酒店|民宿|旅馆/)) {
        categoria = 'accommodations';
    }
    // Lugares / Places / Lieux / Orte / 景点
    else if (mensaje.match(/lugar|sitio|monumento|patrimonio|naturaleza|parque|mirador|qué visitar|qué ver|place|monument|heritage|nature|lieu|monument|ort|sehenswürdigkeit|景点|地方|纪念碑|自然/)) {
        categoria = 'places_of_interest';
    }
    // Actividades / Activities / Activités / Aktivitäten / 活动
    else if (mensaje.match(/actividad|excursión|senderismo|deporte|aventura|experiencia|qué hacer|activity|hiking|sport|adventure|expérience|randonnée|aktivität|wandern|活动|徒步|冒险|体验/)) {
        categoria = 'tourist_activities';
    }
    // Eventos / Events / Événements / Veranstaltungen / 活动
    else if (mensaje.match(/evento|festival|concierto|teatro|exposición|fiesta|cultural|qué hay|event|concert|theatre|exhibition|événement|veranstaltung|konzert|演出|节日|音乐会|活动/)) {
        categoria = 'cultural_events';
    }
    // Detección por botones rápidos en idioma actual
    else if (mensaje === i18n.btn_stays.toLowerCase() || mensaje.includes(i18n.btn_stays.toLowerCase())) {
        categoria = 'accommodations';
    }
    else if (mensaje === i18n.btn_places.toLowerCase() || mensaje.includes(i18n.btn_places.toLowerCase())) {
        categoria = 'places_of_interest';
    }
    else if (mensaje === i18n.btn_activities.toLowerCase() || mensaje.includes(i18n.btn_activities.toLowerCase())) {
        categoria = 'tourist_activities';
    }
    else if (mensaje === i18n.btn_events.toLowerCase() || mensaje.includes(i18n.btn_events.toLowerCase())) {
        categoria = 'cultural_events';
    }
    else if (mensaje === i18n.btn_routes.toLowerCase() || mensaje.includes(i18n.btn_routes.toLowerCase())) {
        categoria = 'routes';
    }

    if (categoria) {
        return { tipo: 'categoria', categoria: categoria };
    }

    // 4. Provincia sola (sin categoría)
    if (provinciaDetectada) {
        return { tipo: 'provincia_sola', provincia: provinciaDetectada };
    }

    // 5. Búsqueda con términos específicos
    const terminos = extraerTerminosBusqueda(mensaje);
    if (terminos.length > 0) {
        let cat = determinarCategoriaPorTerminos(terminos);
        return { tipo: 'busqueda', categoria: cat, terminos: terminos };
    }

    // 6. Ayuda
    if (mensaje.match(/ayuda|qué puedes|qué sabes|información|capacidades|help|what can you|information|aide|capacités|hilfe|was kannst du|帮助|你能做什么/)) {
        return { tipo: 'ayuda' };
    }

    // 7. Búsqueda global
    const palabras = mensaje.split(/\s+/).filter(p => p.length > 2);
    if (palabras.length > 0 && palabras.length <= 4) {
        const termino = mensaje.replace(/^(busca|encuentra|dime|sabes|conoces|qué|como|donde|dónde|show|find|search|cherche|trouve|suche|finde|搜索|找|查找)\s+/i, '').trim();
        if (termino.length >= 3) {
            return { tipo: 'busqueda_global', termino: termino };
        }
    }

    // 8. Desconocido
    return { tipo: 'desconocido' };
}


function extraerTerminosBusqueda(mensaje) {
    const palabrasClave = [
        'numancia', 'laguna negra', 'urbión', 'cañón', 'río lobos',
        'senderismo', 'setas', 'micología', 'astronomía', 'starlight',
        'teatro', 'concierto', 'festival', 'jornadas',
        'casa rural', 'hotel', 'apartamento', 'camping',
        'románico', 'castillo', 'monasterio', 'iglesia',
        'gastronomía', 'vino', 'enoturismo',
        // English
        'hiking', 'mushrooms', 'astronomy', 'theater', 'concert',
        'castle', 'monastery', 'gastronomy', 'wine',
        // French
        'randonnée', 'champignons', 'astronomie', 'théâtre',
        'château', 'monastère', 'gastronomie',
        // German
        'wandern', 'pilze', 'astronomie', 'theater',
        'burg', 'kloster', 'gastronomie'
    ];
    return palabrasClave.filter(palabra => mensaje.includes(palabra));
}

function determinarCategoriaPorTerminos(terminos) {
    const categoriasPorTermino = {
        'numancia': 'places_of_interest', 'laguna negra': 'places_of_interest',
        'urbión': 'places_of_interest', 'cañón': 'places_of_interest',
        'río lobos': 'places_of_interest', 'románico': 'places_of_interest',
        'castillo': 'places_of_interest', 'monasterio': 'places_of_interest',
        'iglesia': 'places_of_interest', 'castle': 'places_of_interest',
        'monastery': 'places_of_interest', 'château': 'places_of_interest',
        'burg': 'places_of_interest', 'kloster': 'places_of_interest',
        'senderismo': 'tourist_activities', 'setas': 'tourist_activities',
        'micología': 'tourist_activities', 'astronomía': 'tourist_activities',
        'starlight': 'tourist_activities', 'hiking': 'tourist_activities',
        'mushrooms': 'tourist_activities', 'astronomy': 'tourist_activities',
        'randonnée': 'tourist_activities', 'champignons': 'tourist_activities',
        'wandern': 'tourist_activities', 'pilze': 'tourist_activities',
        'teatro': 'cultural_events', 'concierto': 'cultural_events',
        'festival': 'cultural_events', 'jornadas': 'cultural_events',
        'theater': 'cultural_events', 'concert': 'cultural_events',
        'théâtre': 'cultural_events',
        'casa rural': 'accommodations', 'hotel': 'accommodations',
        'apartamento': 'accommodations', 'camping': 'accommodations',
        'gastronomía': 'tourist_activities', 'vino': 'tourist_activities',
        'enoturismo': 'tourist_activities', 'gastronomy': 'tourist_activities',
        'wine': 'tourist_activities', 'gastronomie': 'tourist_activities'
    };
    for (const termino of terminos) {
        if (categoriasPorTermino[termino]) return categoriasPorTermino[termino];
    }
    return 'accommodations';
}

// ===============================================
// RESPUESTAS
// ===============================================

function responderSaludo() {
    agregarMensajeBot(i18n.greeting);
}

function mostrarCategoria(categoria) {
    const datos = antonioDatabase[categoria];
    const infoCategoria = antonioState.categorias[categoria];

    if (!datos || datos.length === 0) {
        agregarMensajeBot(i18n.updating(infoCategoria.nombre.toLowerCase()));

        let urlPrincipal = '#';
        switch(categoria) {
            case 'accommodations':     urlPrincipal = '/alojamientos-turisticos.html'; break;
            case 'places_of_interest': urlPrincipal = '/lugares-interes-paginacion.html'; break;
            case 'tourist_activities': urlPrincipal = '/actividades-turisticas.html'; break;
            case 'cultural_events':    urlPrincipal = '/eventos-culturales-paginacion.html'; break;
            case 'routes':             urlPrincipal = '/rutas/'; break;
        }

        agregarMensajeBot(`<div class="antonio-suggestions">
            <div class="antonio-suggestions-list">
                <a href="${urlPrincipal}" class="antonio-suggestion-btn" style="text-decoration:none;display:inline-block;background:var(--antonio-accent);color:white;">
                    ${i18n.explore_now(infoCategoria.nombre.toLowerCase())}
                </a>
            </div>
        </div>`);
        return;
    }

    // Filtrar por provincia si está seleccionada
    let datosFiltrados = datos;
    if (antonioState.provincia) {
        const provLower = antonioState.provincia.toLowerCase();
        const provNorm = provLower.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        datosFiltrados = datos.filter(item => {
            const provinciaItem = (item.province || '').toLowerCase();
            const ubicacionItem = (item.ubicacion || '').toLowerCase();
            const provinciaNorm = provinciaItem.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const ubicacionNorm = ubicacionItem.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            return provinciaNorm === provNorm ||
                   provinciaNorm.includes(provNorm) ||
                   provNorm.includes(provinciaNorm) ||
                   ubicacionNorm.includes(provNorm) ||
                   provNorm.includes(ubicacionNorm);
        });
    }

    if (datosFiltrados.length === 0) {
        agregarMensajeBot(i18n.no_results(infoCategoria.nombre.toLowerCase(), antonioState.provincia));
        if (antonioState.provincia) {
            agregarMensajeBot(i18n.see_other_zones);
            const boton = `<button class="antonio-suggestion-btn" onclick="mostrarCategoriaSinFiltro('${categoria}')">
                ${infoCategoria.icono} ${i18n.see_all_of(infoCategoria.nombre.toLowerCase())}
            </button>`;
            agregarMensajeBot(`<div class="antonio-suggestions-list" style="margin-top:10px;">${boton}</div>`);
        }
        return;
    }

    let respuesta = `<p>${i18n.showing(infoCategoria.nombre, antonioState.provincia)} ${infoCategoria.icono}:</p>`;
    respuesta += `<div class="antonio-cards-grid">`;
    datosFiltrados.forEach(item => {
        respuesta += crearTarjetaItem(item, infoCategoria);
    });
    respuesta += `</div>`;
    respuesta += generarSugerenciaRelacionada(categoria);
    agregarMensajeBot(respuesta);
}

function mostrarCategoriaSinFiltro(categoria) {
    antonioState.provincia = null;
    mostrarCategoria(categoria);
}

function crearTarjetaItem(item, categoriaInfo) {
    const url = item.url || '#';
    const foto = item.foto || '';
    const tieneFoto = foto && foto.length > 10;

    let metaIzq = '';
    let metaDer = '';

    if (item.ubicacion) metaIzq = `📍 ${item.ubicacion}`;
    else if (item.fecha) metaIzq = `📅 ${item.fecha}`;
    else if (item.duracion) metaIzq = `⏱️ ${item.duracion}`;

    if (item.precio) metaDer = `💰 ${item.precio}`;
    else if (item.entrada) metaDer = `💰 ${item.entrada}`;
    else if (item.dificultad) metaDer = `🏔️ ${item.dificultad}`;

    return `
        <div class="antonio-card" onclick="window.open('${url}','_blank')" style="cursor:pointer;">
            ${tieneFoto ? `<img class="antonio-card-img" src="${foto}" alt="${item.nombre}" loading="lazy" onerror="this.style.display='none'">` : ''}
            <div class="antonio-card-header">
                <div class="antonio-card-icon" style="background:${categoriaInfo.color}20;color:${categoriaInfo.color};">
                    ${item.icono || categoriaInfo.icono}
                </div>
                <h3 class="antonio-card-title">${item.nombre}</h3>
            </div>
            <p class="antonio-card-desc">${item.descripcion || ''}</p>
            <div class="antonio-card-meta">
                <span>${metaIzq}</span>
                <span>${metaDer}</span>
            </div>
            <div style="margin-top:6px;font-size:0.8rem;color:var(--antonio-primary);">
                <i class="fas fa-external-link-alt"></i> ${i18n.see_more}
            </div>
        </div>
    `;
}

function buscarEnCategoria(categoria, terminos) {
    const datos = antonioDatabase[categoria];
    const infoCategoria = antonioState.categorias[categoria];

    if (!datos || datos.length === 0) {
        agregarMensajeBot(i18n.no_info(infoCategoria.nombre.toLowerCase()));
        return;
    }

    let resultados = datos.filter(item => {
        const textoBusqueda = `${item.nombre} ${item.descripcion} ${item.ubicacion || ''} ${item.province || ''}`.toLowerCase();
        const tieneTerminos = terminos.some(termino => textoBusqueda.includes(termino));
        if (antonioState.provincia) {
            const provLower = antonioState.provincia.toLowerCase();
            const provNorm = provLower.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const provinciaItem = (item.province || '').toLowerCase();
            const ubicacionItem = (item.ubicacion || '').toLowerCase();
            const provinciaNorm = provinciaItem.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const ubicacionNorm = ubicacionItem.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const coincideProvincia = provinciaNorm === provNorm ||
                                      provinciaNorm.includes(provNorm) ||
                                      provNorm.includes(provinciaNorm) ||
                                      ubicacionNorm.includes(provNorm) ||
                                      provNorm.includes(ubicacionNorm);
            return tieneTerminos && coincideProvincia;
        }
        return tieneTerminos;
    });

    if (resultados.length === 0) {
        agregarMensajeBot(i18n.no_results_search(infoCategoria.nombre.toLowerCase(), antonioState.provincia));
        const boton = `<button class="antonio-suggestion-btn" onclick="mostrarCategoria('${categoria}')">
            ${infoCategoria.icono} ${i18n.see_all_of(infoCategoria.nombre.toLowerCase())}
        </button>`;
        agregarMensajeBot(`<div class="antonio-suggestions-list" style="margin-top:10px;">${boton}</div>`);
        return;
    }

    let respuesta = i18n.results_found(resultados.length, infoCategoria.nombre.toLowerCase() + ' ' + infoCategoria.icono);
    respuesta += `<div class="antonio-cards-grid">`;
    resultados.forEach(item => {
        respuesta += crearTarjetaItem(item, infoCategoria);
    });
    respuesta += `</div>`;
    respuesta += generarSugerenciaRelacionada(categoria);
    agregarMensajeBot(respuesta);
}

function generarSugerenciaRelacionada(categoriaActual) {
    let categoriasRelacionadas = [];
    switch(categoriaActual) {
        case 'accommodations':     categoriasRelacionadas = ['places_of_interest', 'tourist_activities']; break;
        case 'places_of_interest': categoriasRelacionadas = ['tourist_activities', 'routes']; break;
        case 'tourist_activities': categoriasRelacionadas = ['places_of_interest', 'accommodations']; break;
        case 'cultural_events':    categoriasRelacionadas = ['places_of_interest', 'accommodations']; break;
        case 'routes':             categoriasRelacionadas = ['accommodations', 'places_of_interest']; break;
    }
    if (categoriasRelacionadas.length === 0) return '';

    let html = `<div class="antonio-related-suggestions">
        <p style="font-size:0.85rem;color:#666;margin:12px 0 6px;"><i class="fas fa-lightbulb"></i> ${i18n.also_interest}</p>
        <div class="antonio-suggestions-list">`;

    categoriasRelacionadas.forEach(cat => {
        const info = antonioState.categorias[cat];
        if (info) {
            const opcion = cat === 'accommodations' ? 'alojamientos' :
                           cat === 'places_of_interest' ? 'lugares' :
                           cat === 'tourist_activities' ? 'actividades' :
                           cat === 'cultural_events' ? 'eventos' : 'rutas';
            html += `<button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('${opcion}')">
                ${info.icono} ${info.nombre}
            </button>`;
        }
    });

    html += `</div></div>`;
    return html;
}

function mostrarAyuda() {
    agregarMensajeBot(i18n.help_msg);
}

function responderDesconocido() {
    agregarMensajeBot(i18n.unknown_msg);

    setTimeout(() => {
        agregarMensajeBot(`
            <div class="antonio-suggestions">
                <div class="antonio-suggestions-title"><i class="fas fa-bolt"></i> ${i18n.try_with}</div>
                <div class="antonio-suggestions-list">
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">🏨 ${i18n.btn_stays}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">🏛️ ${i18n.btn_places}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">🥾 ${i18n.btn_activities}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">🎭 ${i18n.btn_events}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">🗺️ ${i18n.btn_routes}</button>
                </div>
            </div>
        `);
    }, 500);
}

// ===============================================
// BÚSQUEDA GLOBAL EN TODAS LAS CATEGORÍAS
// ===============================================

function busquedaGlobal(termino) {
    const terminoLower = termino.toLowerCase();
    let todosResultados = [];
    const categoriasConDatos = ['accommodations', 'places_of_interest', 'tourist_activities', 'cultural_events', 'routes'];

    categoriasConDatos.forEach(cat => {
        const datos = antonioDatabase[cat];
        if (!datos || datos.length === 0) return;

        datos.forEach(item => {
            const textoBusqueda = `${item.nombre} ${item.descripcion} ${item.ubicacion || ''} ${item.province || ''}`.toLowerCase();
            if (textoBusqueda.includes(terminoLower)) {
                const infoCat = antonioState.categorias[cat];
                todosResultados.push({
                    item: item,
                    categoria: cat,
                    infoCategoria: infoCat
                });
            }
        });
    });

    if (todosResultados.length === 0) {
        agregarMensajeBot(i18n.not_found(escapeHTML(termino)));
        setTimeout(() => {
            agregarMensajeBot(`
                <div class="antonio-suggestions">
                    <div class="antonio-suggestions-title"><i class="fas fa-bolt"></i> ${i18n.try_with}</div>
                    <div class="antonio-suggestions-list">
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">🏨 ${i18n.btn_stays}</button>
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">🏛️ ${i18n.btn_places}</button>
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">🥾 ${i18n.btn_activities}</button>
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">🎭 ${i18n.btn_events}</button>
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">🗺️ ${i18n.btn_routes}</button>
                    </div>
                </div>
            `);
        }, 500);
        return;
    }

    // Agrupar resultados por categoría
    const agrupados = {};
    todosResultados.forEach(r => {
        if (!agrupados[r.categoria]) agrupados[r.categoria] = [];
        agrupados[r.categoria].push(r);
    });

    let respuesta = i18n.found_results(todosResultados.length, escapeHTML(termino));

    Object.keys(agrupados).forEach(cat => {
        const items = agrupados[cat];
        const infoCat = items[0].infoCategoria;
        respuesta += `<div style="margin:10px 0 4px;">
            <span style="font-weight:600;color:${infoCat.color};">${infoCat.icono} ${infoCat.nombre} (${items.length})</span>
        </div>`;
        respuesta += `<div class="antonio-cards-grid">`;
        items.forEach(r => {
            respuesta += crearTarjetaItem(r.item, r.infoCategoria);
        });
        respuesta += `</div>`;
    });

    agregarMensajeBot(respuesta);
}

// ===============================================
// RESPUESTA CUANDO SOLO MENCIONA PROVINCIA
// ===============================================

function responderProvinciaSola(provincia) {
    agregarMensajeBot(i18n.province_interest(provincia));

    setTimeout(() => {
        agregarMensajeBot(`
            <div class="antonio-suggestions">
                <div class="antonio-suggestions-title"><i class="fas fa-bolt"></i> ${i18n.choose_option}</div>
                <div class="antonio-suggestions-list">
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">${i18n.stays_in(provincia)}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">${i18n.places_in(provincia)}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">${i18n.activities_in(provincia)}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">${i18n.events_in(provincia)}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">${i18n.routes_in(provincia)}</button>
                </div>
            </div>
        `);
    }, 500);
}

// ===============================================
// MANEJO DE MENSAJES EN EL CHAT
// ===============================================

function agregarMensajeUsuario(texto) {
    const messages = document.getElementById('antonio-messages');
    const div = document.createElement('div');
    div.className = 'antonio-message antonio-user';
    div.innerHTML = `<div class="antonio-bubble">${escapeHTML(texto)}</div>`;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}

function agregarMensajeBot(html) {
    const messages = document.getElementById('antonio-messages');
    const div = document.createElement('div');
    div.className = 'antonio-message antonio-bot';
    div.innerHTML = `<div class="antonio-bubble">${html}</div>`;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}

function mostrarTypingIndicator() {
    const messages = document.getElementById('antonio-messages');
    const div = document.createElement('div');
    div.className = 'antonio-message antonio-bot';
    div.id = 'antonio-typing';
    div.innerHTML = `<div class="antonio-bubble">
        <div class="antonio-typing-indicator">
            <span></span><span></span><span></span>
        </div>
    </div>`;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}

function ocultarTypingIndicator() {
    const typing = document.getElementById('antonio-typing');
    if (typing) typing.remove();
}

function escapeHTML(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
