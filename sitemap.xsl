<?xml version="1.0" encoding="UTF-8"?>
<!--
  HOJA DE ESTILO XSLT PARA SITEMAPS — rutasrurales.io
  Hace que los archivos XML del sitemap sean legibles en el navegador
  sin extensiones ni herramientas externas.
-->
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:sm="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:xhtml="http://www.w3.org/1999/xhtml"
  exclude-result-prefixes="sm xhtml">

<xsl:output method="html" encoding="UTF-8" indent="yes"/>

<xsl:template match="/">
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sitemap — rutasrurales.io</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    body  { font-family: system-ui, sans-serif; background: #f8f9fa; }
    h1    { font-size: 1.4rem; }
    code  { font-size: .78rem; word-break: break-all; }
    .hreflang-tags { font-size: .72rem; color: #555; }
    .badge-lang { font-size: .65rem; }
    tr:hover td { background: #e9f5ff !important; }
  </style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex align-items-center gap-3 mb-4">
    <img src="https://rutasrurales.io/favicon.png" width="36" height="36" alt="Logo" onerror="this.style.display='none'"/>
    <div>
      <h1 class="mb-0 fw-bold">🗺️ Sitemap XML</h1>
      <p class="mb-0 text-muted small">
        <a href="https://rutasrurales.io" target="_blank">rutasrurales.io</a>
        — <xsl:value-of select="count(sm:urlset/sm:url)"/> URLs indexadas
      </p>
    </div>
  </div>

  <xsl:if test="sm:sitemapindex">
    <!-- Índice de sitemaps -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-dark text-white">
        <strong>📑 Índice de Sitemaps</strong>
        — <xsl:value-of select="count(sm:sitemapindex/sm:sitemap)"/> archivos
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <thead class="table-secondary">
            <tr><th>#</th><th>URL del Sitemap</th><th>Última modificación</th></tr>
          </thead>
          <tbody>
            <xsl:for-each select="sm:sitemapindex/sm:sitemap">
              <xsl:sort select="sm:loc"/>
              <tr>
                <td class="text-muted"><xsl:value-of select="position()"/></td>
                <td><a href="{sm:loc}" target="_blank"><code><xsl:value-of select="sm:loc"/></code></a></td>
                <td><xsl:value-of select="sm:lastmod"/></td>
              </tr>
            </xsl:for-each>
          </tbody>
        </table>
      </div>
    </div>
  </xsl:if>

  <xsl:if test="sm:urlset">
    <!-- Listado de URLs -->
    <div class="card shadow-sm border-0">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <strong>🔗 URLs del Sitemap</strong>
        <span class="badge bg-white text-primary">
          <xsl:value-of select="count(sm:urlset/sm:url)"/> URLs
        </span>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th style="width:2rem">#</th>
              <th>URL (loc)</th>
              <th style="width:7rem">lastmod</th>
              <th style="width:6rem">changefreq</th>
              <th style="width:4rem">priority</th>
              <th>hreflang</th>
            </tr>
          </thead>
          <tbody>
            <xsl:for-each select="sm:urlset/sm:url">
              <tr>
                <td class="text-muted"><xsl:value-of select="position()"/></td>
                <td>
                  <a href="{sm:loc}" target="_blank">
                    <code><xsl:value-of select="sm:loc"/></code>
                  </a>
                </td>
                <td><xsl:value-of select="sm:lastmod"/></td>
                <td><xsl:value-of select="sm:changefreq"/></td>
                <td>
                  <xsl:choose>
                    <xsl:when test="sm:priority &gt;= 0.9">
                      <span class="badge bg-success"><xsl:value-of select="sm:priority"/></span>
                    </xsl:when>
                    <xsl:when test="sm:priority &gt;= 0.7">
                      <span class="badge bg-primary"><xsl:value-of select="sm:priority"/></span>
                    </xsl:when>
                    <xsl:otherwise>
                      <span class="badge bg-secondary"><xsl:value-of select="sm:priority"/></span>
                    </xsl:otherwise>
                  </xsl:choose>
                </td>
                <td class="hreflang-tags">
                  <xsl:for-each select="xhtml:link">
                    <span class="badge bg-light text-dark border badge-lang me-1">
                      <xsl:value-of select="@hreflang"/>
                    </span>
                  </xsl:for-each>
                </td>
              </tr>
            </xsl:for-each>
          </tbody>
        </table>
      </div>
    </div>

    <p class="text-muted small mt-3 text-center">
      Sitemap generado por rutasrurales.io ·
      Visualización mediante hoja de estilo XSLT ·
      <a href="https://www.sitemaps.org/protocol.html" target="_blank">Protocolo Sitemaps 0.9</a>
    </p>
  </xsl:if>

</div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
