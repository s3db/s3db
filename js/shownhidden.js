function shownhidden(id) 
{
	details = document.getElementById(id);
	if (details.className=="shown")
 	{ 
		details.className="hidden"; 
	}
	else 
	{ 
		details.className="shown";
	}
}
