<?xml version="1.0" encoding="UTF-8"?>
<!-- 

Clean html extracted from epub of some oddities

-->
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
  xmlns="http://www.w3.org/1999/xhtml" 
  xmlns:html="http://www.w3.org/1999/xhtml"
  xmlns:epub="http://www.idpf.org/2007/ops"
  exclude-result-prefixes="html"
  >
  <xsl:output indent="yes" encoding="UTF-8" method="xml" omit-xml-declaration="yes"/>
  <!-- space separated css class name to strip -->
  <xsl:param name="class_exclude"> chp italic niv1p txt </xsl:param>
  <xsl:variable name="class_ex" select="concat(' ', $class_exclude, ' ')"/>
  <xsl:variable name="class_in"/>
  <!-- load a possible css island  -->
  <xsl:key name="css" match="html:css/html:rule" use="@selector"/>
  <xsl:template match="node()|@*">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>
  
  <xsl:template match="html:section[html:section]">
    <xsl:choose>
      <xsl:when test="count(*) = 1 and html:section[@epub:type]">
        <xsl:apply-templates/>
      </xsl:when>
      <xsl:when test="count(*) = 1 and html:section">
        <xsl:copy>
          <xsl:copy-of select="@*"/>
          <xsl:apply-templates select="html:section/node()"/>
        </xsl:copy>
      </xsl:when>
      <xsl:otherwise>
        <xsl:copy>
          <xsl:copy-of select="@*"/>
          <xsl:apply-templates/>
        </xsl:copy>
      </xsl:otherwise>
    </xsl:choose>
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
          <xsl:apply-templates select="node()|@*[name() != 'class']"/>
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

  <xsl:template name="class">
    <xsl:param name="suffix"/>
    <xsl:variable name="class">
      <xsl:variable name="text">
        <xsl:call-template name="class_clean">
          <xsl:with-param name="class" select="@class"/>
        </xsl:call-template>
        <xsl:text> </xsl:text>
        <xsl:value-of select="$suffix"/>
      </xsl:variable>
      <xsl:value-of select="normalize-space($text)"/>
    </xsl:variable>
    <xsl:if test="$class != ''">
      <xsl:attribute name="class">
        <xsl:value-of select="$class"/>
      </xsl:attribute>
    </xsl:if>
  </xsl:template>

  <xsl:template match="html:p">
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:variable name="props">
      <xsl:call-template name="class_props"/>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="false()"/>
      <xsl:otherwise>
        <p>
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:call-template name="class">
            <xsl:with-param name="suffix" select="$props"/>
          </xsl:call-template>
          <xsl:apply-templates/>
        </p>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="html:div">
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:variable name="props">
      <xsl:call-template name="class_props">
        <xsl:with-param name="class" select="@class"/>
      </xsl:call-template>
    </xsl:variable>


    <xsl:choose>
      <!-- with a title, let’s bet it is a section -->
      <xsl:when test="html:h1 | html:h2 | html:h3">
        <section>
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:call-template name="class">
            <xsl:with-param name="suffix" select="$props"/>
          </xsl:call-template>
          <xsl:apply-templates/>
        </section>
      </xsl:when>
      <!-- text content, a para <p>? -->
      <xsl:when test="$mixed != ''">
        <p>
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:call-template name="class">
            <xsl:with-param name="suffix" select="$props"/>
          </xsl:call-template>
          <xsl:apply-templates/>
        </p>
      </xsl:when>
      <!-- One <i> -->
      <!--
      <xsl:when test="$mixed = '' and count(*) = 1 and html:i">
        <xsl:call-template name="div">
          <xsl:with-param name="style">i</xsl:with-param>
        </xsl:call-template>
      </xsl:when>
      -->
      <xsl:when test="html:img">
        <figure>
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:call-template name="class">
            <xsl:with-param name="suffix" select="$props"/>
          </xsl:call-template>
          <xsl:apply-templates/>
        </figure>
      </xsl:when>
      <!-- usually spacing -->
      <xsl:when test="html:br">
        <p> </p>
      </xsl:when>
      <xsl:otherwise>
        <div>
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:call-template name="class">
            <xsl:with-param name="suffix" select="$props"/>
          </xsl:call-template>
          <xsl:apply-templates/>
        </div>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  
  <xsl:template name="div__">
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
        <!-- automatic style, keep ? -->
        <!--
        <xsl:when test="translate(substring($class, 2), '0123456789', '') = ''">
          
        </xsl:when>
        -->
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
        <xsl:for-each select="key('css', concat('.', $norm))">
          <xsl:for-each select="html:declaration">
            <xsl:text> </xsl:text>
            <xsl:value-of select="@value"/>
            <xsl:text> </xsl:text>
          </xsl:for-each>
        </xsl:for-each>
        <xsl:for-each select="key('css', concat('span.', $norm))">
          <xsl:for-each select="html:declaration">
            <xsl:text> </xsl:text>
            <xsl:value-of select="@value"/>
            <xsl:text> </xsl:text>
          </xsl:for-each>
        </xsl:for-each>
        <xsl:for-each select="key('css', concat('div.', $norm))">
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