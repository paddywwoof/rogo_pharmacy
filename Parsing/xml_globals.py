######################## global variables
#### List TD Questions 
## head 8 variables {}{}{}{}{}{}{}{} ===> name, multiselect, description, multiselect (again!!), you_version, report_version, significant no, voluntary
LIST_TD_Q_HEAD = """  <item name="{}" type="list" category="Tree_Dialog_Questions" parentobj="List_TD_Question" multiselect="{}" hastree="false" hascases="false" >
    <flatprops>
      <description exposedout="true" length="255" xmlout="true"><![CDATA[{}]]></description>{}{}{}{}{}
    </flatprops>
    <valueprops>
      <listDesc type="string" displayno="-1" displaywidth="80" ishtml="false" hierarchyout="false" constraints="false" xmlout="false" exposedout="true" translate="false" flags="" trawlexport="false" length="255" />
      <values>
"""
## item 2 variables {}{} ===> value, description
LIST_TD_Q_ITEM = """        <item>
          <name><![CDATA[{}]]></name>
          <listDesc><![CDATA[{}]]></listDesc>
        </item>
"""
## tail no variables
LIST_TD_Q_TAIL = """      </values>
    </valueprops>
  </item>
"""

#### Numeric TD Questions 4 variables {}{}{}{} ===> name, description, you_version, report_version
NUMERIC_TD_Q = """  <item name="{}" type="numeric" category="Tree_Dialog_Questions" parentobj="Numeric_TD_Question" hastree="false" hascases="false" >
    <flatprops>
      <description exposedout="true" length="255"><![CDATA[{}]]></description>{}{}
    </flatprops>
  </item>
"""

#### Text TD Questions 4 variables {}{} ===> name, description, you_version, report_version
TEXT_TD_Q = """  <item name="{}" type="text" category="Tree_Dialog_Questions" parentobj="Text_TD_Question" hastree="false" hascases="false" >
    <flatprops>
      <description exposedout="true" length="255"><![CDATA[{}]]></description>{}{}
    </flatprops>
  </item>
"""

#### Boolean TD Questions 6 variables {}{}{}{} ===> name, description, you_version, report_version, value1, value2
BOOLEAN_TD_Q = """  <item name="{}" type="boolean" category="Tree_Dialog_Questions" parentobj="Boolean_TD_Question" hastree="false" hascases="false" >
    <flatprops>
      <description exposedout="true" length="255"><![CDATA[{}]]></description>{}{}
    </flatprops>
    <valueprops>
      <values>
        <item>
          <name><![CDATA[{}]]></name>
        </item>
        <item>
          <name><![CDATA[{}]]></name>
        </item>
      </values>
    </valueprops>
  </item>
"""
### Boolean Attribute as used for hotspots on images 2 variables {}{}{} ===> name, description, question
BOOLEAN_ATTR = """  <item name="{}" type="boolean" category="Attributes" parentobj="Boolean_Attribute" hastree="false" hascases="false" >
    <flatprops>
      <description exposedout="true" length="255"><![CDATA[{}]]></description>
      <question exposedout="true" length="255"><![CDATA[{}]]></question>
      <default>false</default>
    </flatprops>
    <valueprops>
      <values>
        <item>
          <name><![CDATA[Yes]]></name>
          <vDescription></vDescription>
        </item>
        <item>
          <name><![CDATA[No]]></name>
          <vDescription></vDescription>
        </item>
      </values>
    </valueprops>
  </item>

"""
### Dialog form for image and hotspots 5 variables {}{}{}{}{} ===> name, description, description, hotspot_list_a, list_attr_name, description
DIALOG = """  <item name="{}" type="dialog" category="Dialogs">
    <parentdialog>Master_Form</parentdialog>
    <isxml>false</isxml>
    <dialogprop deploymentmedia="Windows Desktop" >
      <left>0</left>
      <top>0</top>
      <width>500</width>
      <height>300</height>
      <hascaption>true</hascaption>
      <centered>true</centered>
      <caption><![CDATA[{}]]></caption>
      <color>clNone#F0F0F0</color>
      <onenter></onenter>
      <onshow></onshow>
      <onexit></onexit>
      <oncanexit></oncanexit>
      <initialupdates>true</initialupdates>
      <timeout>0</timeout>
      <bkgndimage><![CDATA[]]></bkgndimage>
      <showpreviousans>true</showpreviousans>
      <resizable>false</resizable>
      <font>
        <height>-11</height>
        <width>0</width>
        <escapement>0</escapement>
        <orientation>0</orientation>
        <weight>400</weight>
        <italic>0</italic>
        <underline>0</underline>
        <strikeout>0</strikeout>
        <charSet>0</charSet>
        <outprecision>0</outprecision>
        <clipprecision>0</clipprecision>
        <quality>0</quality>
        <pitchandfamily>0</pitchandfamily>
        <facename>MS Shell Dlg</facename>
      </font>
      <cssclassname><![CDATA[]]></cssclassname>
      <controls>
      </controls>
      <webinfhtmlpage><![CDATA[]]></webinfhtmlpage>
      <description><![CDATA[]]></description>
      <includeindocumentation>true</includeindocumentation>
      <instanceatts locked="false">
      </instanceatts>
    </dialogprop>
    <dialogprop deploymentmedia="Fixed Layout" >
      <left>0</left>
      <top>0</top>
      <width>500</width>
      <height>300</height>
      <hascaption>true</hascaption>
      <centered>true</centered>
      <caption><![CDATA[{}]]></caption>
      <color>clNone#F0F0F0</color>
      <onenter></onenter>
      <onshow></onshow>
      <onexit></onexit>
      <oncanexit></oncanexit>
      <initialupdates>true</initialupdates>
      <timeout>0</timeout>
      <bkgndimage><![CDATA[]]></bkgndimage>
      <showpreviousans>true</showpreviousans>
      <resizable>false</resizable>
      <font>
        <height>-11</height>
        <width>0</width>
        <escapement>0</escapement>
        <orientation>0</orientation>
        <weight>400</weight>
        <italic>0</italic>
        <underline>0</underline>
        <strikeout>0</strikeout>
        <charSet>0</charSet>
        <outprecision>0</outprecision>
        <clipprecision>0</clipprecision>
        <quality>0</quality>
        <pitchandfamily>0</pitchandfamily>
        <facename>MS Shell Dlg</facename>
      </font>
      <cssclassname><![CDATA[]]></cssclassname>
      <controls>
        <control name="image" type="bitmap" >
          <left>1</left>
          <top>1</top>
          <width>717</width>
          <height>629</height>
          <tabno>0</tabno>
          <imagepath><![CDATA[assets\male1.jpg]]></imagepath>
          <istransparent>false</istransparent>
          <transparentcolor>clWhite#FFFFFF</transparentcolor>
          <drawmode>Clipped</drawmode>
          <image></image>
          <visible>true</visible>
          <cssclassname><![CDATA[]]></cssclassname>
          <alttext><![CDATA[]]></alttext>
          <zoomable>true</zoomable>
        </control>
{}      </controls>
      <webinfhtmlpage><![CDATA[]]></webinfhtmlpage>
      <description><![CDATA[]]></description>
      <includeindocumentation>true</includeindocumentation>
      <instanceatts locked="false">
        <attinst name="{}" isvisible="true" />
      </instanceatts>
    </dialogprop>
    <dialogprop deploymentmedia="Fluid Layout" >
      <left>0</left>
      <top>0</top>
      <width>500</width>
      <height>300</height>
      <hascaption>true</hascaption>
      <centered>true</centered>
      <caption><![CDATA[{}]]></caption>
      <color>clNone#F0F0F0</color>
      <onenter></onenter>
      <onshow></onshow>
      <onexit></onexit>
      <oncanexit></oncanexit>
      <initialupdates>true</initialupdates>
      <timeout>0</timeout>
      <bkgndimage><![CDATA[]]></bkgndimage>
      <showpreviousans>true</showpreviousans>
      <resizable>false</resizable>
      <font>
        <height>-11</height>
        <width>0</width>
        <escapement>0</escapement>
        <orientation>0</orientation>
        <weight>400</weight>
        <italic>0</italic>
        <underline>0</underline>
        <strikeout>0</strikeout>
        <charSet>0</charSet>
        <outprecision>0</outprecision>
        <clipprecision>0</clipprecision>
        <quality>0</quality>
        <pitchandfamily>0</pitchandfamily>
        <facename>MS Shell Dlg</facename>
      </font>
      <cssclassname><![CDATA[]]></cssclassname>
      <controls>
      </controls>
      <webinfhtmlpage><![CDATA[]]></webinfhtmlpage>
      <description><![CDATA[]]></description>
      <includeindocumentation>true</includeindocumentation>
      <instanceatts locked="false">
      </instanceatts>
    </dialogprop>
  </item>
"""
### hotspot item a 7 variables {}{}{}{}{}{}{} ===> hotspot_name, left, top, width, height, list_name, list_value
HOTSPOT_A = """        <control name="{}" type="hotspot" isinherited="false" >
          <left>{}</left>
          <top>{}</top>
          <width>{}</width>
          <height>{}</height>
          <tabno>0</tabno>
          <tieditem>{}</tieditem>
          <showoutline>true</showoutline>
          <showborder>true</showborder>
          <value>{}</value>
          <visible>true</visible>
          <cssclassname><![CDATA[]]></cssclassname>
          <validation><![CDATA[]]></validation>
        </control>
"""
### alternative text
ALT_TEXT = """
      <altText exposedout="true" userdefined="true"  length="255"><![CDATA[{}]]></altText>"""
### you version text
YOU_TEXT = """
      <youText exposedout="true" userdefined="true"  length="255"><![CDATA[{}]]></youText>"""
### significant negative flag
SIG_TEXT = """
      <sigFlag userdefined="true"  exposedout="true">true</sigFlag>"""
#######################
QOFF = ord('Q') - ord('A')
ROFF = ord('R') - ord('A')
SOFF = ord('S') - ord('A')
####################### read csv file
