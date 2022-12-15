<?xml version="1.0" encoding="UTF-8"?>
<!-- 

Clean html extracted from epub of some oddities

-->
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
  xmlns="http://www.w3.org/1999/xhtml" 
  xmlns:html="http://www.w3.org/1999/xhtml"
  exclude-result-prefixes="html"
  >
  <xsl:output indent="yes" encoding="UTF-8" method="xml" omit-xml-declaration="yes"/>
  <!-- space separated css class name to strip -->
  <xsl:param name="class_exclude"> chp italic niv1p </xsl:param>
  <xsl:variable name="class_ex" select="concat(' ', $class_exclude, ' ')"/>
  <xsl:variable name="class_in"/>
  <!-- load a possible css island  -->
  <xsl:key name="css" match="html:css/html:rule" use="@selector"/>
  <xsl:template match="node()|@*">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>
  
  
  
  <!-- semantize some elements -->
  <xsl:template match="html:span[@class]">
    <xsl:variable name="props">
      <xsl:call-template name="class_props">
        <xsl:with-param name="class" select="@class"/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="contains($props, ' italic ')">
        <i>
          <xsl:apply-templates select="node()|@*"/>
        </i>
      </xsl:when>
      <xsl:otherwise>
        <xsl:copy>
          <xsl:apply-templates select="node()|@*"/>
        </xsl:copy>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="mixed">
    <xsl:variable name="text">
      <xsl:for-each select="text()">
        <xsl:value-of select="."/>
      </xsl:for-each>
    </xsl:variable>
    <xsl:value-of select="normalize-space(.)"/>
  </xsl:template>

  <xsl:template match="html:p | html:div">
    <xsl:variable name="norm" select="normalize-space(.)"/>
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:choose>
      <!-- One <i> -->
      <xsl:when test="$mixed = '' and count(*) = 1 and html:i">
        <xsl:call-template name="div">
          <xsl:with-param name="style">i</xsl:with-param>
        </xsl:call-template>
      </xsl:when>
      <!-- text, no question -->
      <xsl:when test="$norm != ''">
        <xsl:call-template name="div"/>
      </xsl:when>
      <xsl:when test="html:img">
        <xsl:call-template name="div">
          <xsl:with-param name="element">figure</xsl:with-param>
        </xsl:call-template>
      </xsl:when>
      <!-- usually spacing -->
      <xsl:when test="html:br">
        <p> </p>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
  
  
  <xsl:template name="div">
    <xsl:param name="style"/>
    <xsl:param name="element" select="name()"/>
    <xsl:variable name="props">
      <xsl:call-template name="class_props">
        <xsl:with-param name="class" select="@class"/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:variable name="class">
      <xsl:call-template name="class_clean">
        <xsl:with-param name="class" select="@class"/>
      </xsl:call-template>
      <xsl:text> </xsl:text>
      <xsl:value-of select="$props"/>
      <xsl:text> </xsl:text>
      <xsl:value-of select="$style"/>
    </xsl:variable>
    <xsl:element name="{$element}">
      <xsl:apply-templates select="@*[local-name() != 'class']"/>
      <xsl:choose>
        <xsl:when test="normalize-space($class) != ''">
        <xsl:attribute name="class">
          <xsl:value-of select="normalize-space($class)"/>
        </xsl:attribute>
        </xsl:when>
      </xsl:choose>
      <xsl:apply-templates/>
    </xsl:element>
  </xsl:template>

  <xsl:template match="html:template[@id='css']"/>

  <xsl:template match="@class">
    <xsl:variable name="class">
      <xsl:call-template name="class_clean">
        <xsl:with-param name="class" select="."/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="$class != ''">
        <xsl:attribute name="class">
          <xsl:value-of select="$class"/>
        </xsl:attribute>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <!-- delete different class -->
  <xsl:template name="class_clean">
    <xsl:param name="class"/>
    <xsl:variable name="class_clean">
      <xsl:call-template name="class_cleaner">
        <xsl:with-param name="class" select="normalize-space($class)"/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:value-of select="normalize-space($class_clean)"/>
  </xsl:template>

  <xsl:template name="class_cleaner">
    <xsl:param name="class"/>
    <xsl:variable name="norm" select="normalize-space($class)"/>
    <xsl:choose>
      <xsl:when test="$norm = ''"/>
      <xsl:when test="contains($norm, ' ')">
        <xsl:call-template name="class_cleaner">
          <xsl:with-param name="class" select="substring-before($norm, ' ')"/>
        </xsl:call-template>
        <xsl:call-template name="class_cleaner">
          <xsl:with-param name="class" select="substring-after($norm, ' ')"/>
        </xsl:call-template>
      </xsl:when>
      <!-- keep only include -->
      <xsl:when test="$class_in != ''">
        <xsl:if test="contains($class_in, concat(' ', $norm, ' '))">
          <xsl:text> </xsl:text>
          <xsl:value-of select="$norm"/>
        </xsl:if>
      </xsl:when>
      <xsl:when test="contains($class_ex, concat(' ', $norm, ' '))"/>
      <xsl:otherwise>
        <xsl:text> </xsl:text>
        <xsl:value-of select="$norm"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="class_props">
    <xsl:param name="class" select="normalize-space(@class)"/>
    <xsl:variable name="norm" select="normalize-space($class)"/>
    <xsl:choose>
      <xsl:when test="$norm = ''"/>
      <xsl:when test="contains($norm, ' ')">
        <xsl:call-template name="class_props">
          <xsl:with-param name="class" select="substring-before($norm, ' ')"/>
        </xsl:call-template>
        <xsl:call-template name="class_props">
          <xsl:with-param name="class" select="substring-after($norm, ' ')"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:copy-of select="key('css', concat('.', $norm))"/>
        <xsl:for-each select="key('css', concat('.', $norm))">
          <xsl:for-each select="html:declaration">
            <xsl:text> </xsl:text>
            <xsl:value-of select="@value"/>
            <xsl:text> </xsl:text>
          </xsl:for-each>
        </xsl:for-each>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:transform>