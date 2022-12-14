<?xml version="1.0" encoding="UTF-8"?>
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
  xmlns:ncx="http://www.daisy.org/z3986/2005/ncx/"
  xmlns="http://www.w3.org/1999/xhtml"
  
   exclude-result-prefixes="ncx"
>
  <xsl:output indent="yes" encoding="UTF-8" omit-xml-declaration="yes"/>
  <xsl:template match="*">
    <xsl:copy>
      <xsl:copy-of select="@*"/>
      <xsl:apply-templates/>
    </xsl:copy>
  </xsl:template>
  <xsl:template match="ncx:ncx">
    <xsl:apply-templates select="ncx:navMap"/>
  </xsl:template>
  <xsl:template match="ncx:navMap">
    <body>
      <xsl:apply-templates/>
    </body>
  </xsl:template>
  <xsl:template match="ncx:navPoint">
    <section>
      <xsl:if test="ncx:navLabel">
        <xsl:attribute name="title">
          <xsl:value-of select="normalize-space(ncx:navLabel)"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:variable name="file"/>
      <xsl:apply-templates select="ncx:content"/>
      <xsl:apply-templates select="ncx:navPoint"/>
    </section>
  </xsl:template>
  <xsl:template match="ncx:content">
    <xsl:variable name="file" select="substring-before(concat(@src, '#'), '#')"/>
    <xsl:variable name="next" select="following::ncx:content[1]"/>
    <xsl:variable name="next-file" select="substring-before(concat($next/@src, '#'), '#')"/>
    <content>
      <xsl:attribute name="src">
        <xsl:value-of select="@src"/>
      </xsl:attribute>
      <!-- if same file, hope with an id, for end of text to extract -->
      <xsl:if test="$file = $next-file">
        <xsl:attribute name="to">
          <xsl:value-of select="$next/@src"/>
        </xsl:attribute>
      </xsl:if>
    </content>
  </xsl:template>
</xsl:transform>