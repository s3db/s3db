// DcomboBox.js
// Copywrite 2004 2005 Martin Krolik (martin@krolik.net)
// All rights reserved.  
var arrAllComboBoxes = new Array();
var pOldDComboBoxOnloadHandler = null;
var pOldDComboBoxResizeHandler = null;
pOldDComboBoxOnloadHandler = window.onload;
pOldDComboBoxResizeHandler = window.onresize;
onload =  DComboBoxOnloadHandler;
onresize = DComboBoxResizeHandler;
function ComboInit(BaseName)
{
	var objA, objB, offset;
	offset = 0;
	if (navigator.appName.indexOf("Mozilla")>=0) offset = -1;
	objA = xGetElementById(BaseName);
	objB = xGetElementById(BaseName + "DcboBox");
	xMoveTo(objB, xPageX(objA) + offset, xPageY(objA));	
	xClip(objB , 0, xWidth(objB), xHeight(objB), xWidth(objB) - 23);
	xShow(objB);
	UnSelectAnyOptions(objB);
}
function AllComboInit()
{
	for (var i = 0; i < arrAllComboBoxes.length; i++) {
		ComboInit(arrAllComboBoxes[i]);
	}
}
function DComboBoxOnloadHandler()
{
	if (pOldDComboBoxOnloadHandler) pOldDComboBoxOnloadHandler();
	AllComboInit();
}
function DComboBoxResizeHandler()
{
	if (pOldDComboBoxResizeHandler) pOldDComboBoxResizeHandler();
	AllComboInit();
}
function UpdateDCBoxGeneric(evt)
{
	var objA, objB, strName;
	var objEvent = new xEvent(evt);
	objB = objEvent.target;
	strName = objB.name;	
	strName = strName.split("DcboBox")[0];
	objA = xGetElementById(strName);
	objA.value = objB.options[objB.selectedIndex].text;
	UnSelectAnyOptions(objB);
	//if ((! document.all) && objA && (objA.onchange)) objA.onchange();
	if (objA && (objA.onchange)) objA.onchange();
}
function UnSelectAnyOptions(selectElement)
{
	// this unselects any records
	for (var n = 0; n < selectElement.options.length; n++) {
		selectElement.options[n].selected = false;
	}
	if (selectElement.options.length > 0) { // this is for NS4 on X-Windows
		if (document.layers) {
			// this is for NS4 on X-Windows
			selectElement.options[0].selected = true;
		}
		selectElement.options[0].selected = false;
	}
	selectElement.selectedIndex = -1;
	selectElement.value = null;
}
function BuildDCBox(BaseName, StartValue, Options, PixelWidth, Other, AdditionalStyle)
{
	var DCOptions;
	if (!(PixelWidth)) PixelWidth = 180;
	if (!(StartValue)) StartValue = "";
	if ((typeof Options) == "string")  { DCOptions = Options.split(","); } else { DCOptions = Options; }
	var strBuild = "";
	//var property="text";
	if (document.layers)
		strBuild += "<table border=0><tr><td align=center>";
	strBuild += "<input type='text' value='" + StartValue;
	strBuild += "' name='" + BaseName + "' id='" + BaseName + "' ";
	//strBuild += "onkeyup='autoComplete(this, this.form."+ BaseName +", "+property+", true)' "
	//strBuild += "onkeyup='AutoCompeleteInput(event)' "
	if (!(document.layers))
		strBuild += "style='width: " + (PixelWidth - 19) + "px; position: relative; " + AdditionalStyle + "' ";
	strBuild += Other + " />";
	if (document.layers)
		strBuild += "</td></tr><tr><td align=center>- Or Select -</td></tr><tr><td align=center>";
	else
		strBuild += "<img border='0' height='1' width='23' />";
	strBuild += "<select name='" + BaseName + "DcboBox' id='" + BaseName ;
	strBuild += "DcboBox' ";
	if (!(document.layers))
		strBuild += "style='width: " + PixelWidth + "px; position: absolute; visibility: hidden; " + AdditionalStyle + "' ";
	strBuild += "readonly='true'  onchange='UpdateDCBoxGeneric(event);' >" ;
	if ((DCOptions) && (DCOptions.length))
	{
		for (var cnt = 0; cnt < DCOptions.length; cnt++ )
		{
			//strBuild += "<option value='"+ DCOptions[cnt]+"'>" + DCOptions[cnt] + "</option>";
			strBuild += "<option>" + DCOptions[cnt] + "</option>";
		}
	}
	strBuild += "</select>";
	if (document.layers)
		strBuild += "</td></tr></table>";
	return (strBuild);
}
function WriteDCBox(BaseName, StartValue, Options, PixelWidth, Other, AdditionalStyle)
{
	if (!(BaseName)) BaseName = "DefaultDComboBoxName";
	document.write(BuildDCBox(BaseName,StartValue,Options,PixelWidth,Other,AdditionalStyle));
	setTimeout("ComboInit('" + BaseName + "');", 5);
	arrAllComboBoxes.push(BaseName);
}





