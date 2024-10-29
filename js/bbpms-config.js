function showdivohbother(box) {
	var chboxs = document.getElementsByName("bbpms-rem-ohbother");
	var vis = "block";
	for(var i=0;i<chboxs.length;i++) {
	  if(chboxs[i].checked){
		vis = "none";
		break;
	  }
	}
	document.getElementById(box).style.display = vis;
}
