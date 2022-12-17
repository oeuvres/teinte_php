<?xml version="1.0" encoding="UTF-8"?>
<!-- 

-->
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
  xmlns:ncx="http://www.daisy.org/z3986/2005/ncx/"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:opf="http://www.idpf.org/2007/opf"
   exclude-result-prefixes="ncx"
>
  <xsl:output indent="yes" encoding="UTF-8" omit-xml-declaration="yes"/>
  <xsl:key name="manifest" match="opf:manifest/opf:item" use="@id"/>
  <xsl:key name="href" match="opf:manifest/opf:item" use="@href"/>
  <xsl:key name="spine" match="opf:spine/opf:itemref" use="@idref"/>
  
  <xsl:template match="/">
    <!-- ncx found -->
    <xsl:apply-templates select="/*/ncx:ncx"/>
  </xsl:template>
  
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
    <!-- check spine -->
    <xsl:variable name="id" select="key('href', $file)/@id"/>
    <xsl:for-each select="key('spine', $id)">
      <xsl:apply-templates select="following-sibling::opf:itemref[1]" mode="next">
        <xsl:with-param name="next-file" select="$next-file"/>
      </xsl:apply-templates>
    </xsl:for-each>
  </xsl:template>

  <xsl:template match="opf:itemref" mode="next">
    <xsl:param name="next-file"/>
    <xsl:variable name="href" select="key('manifest', @idref)/@href"/>
    <xsl:variable name="file" select="substring-before(concat($href, '#'), '#')"/>
    <xsl:choose>
      <xsl:when test="$file = $next-file"/>
      <xsl:otherwise>
        <content>
          <xsl:attribute name="src">
            <xsl:value-of select="$href"/>
          </xsl:attribute>
        </content>
        <xsl:apply-templates select="following-sibling::opf:itemref[1]" mode="next">
          <xsl:with-param name="next-file" select="$next-file"/>
        </xsl:apply-templates>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
</xsl:transform>