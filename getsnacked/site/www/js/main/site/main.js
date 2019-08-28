function changeForm(form) {
	document.getElementById("creditCardForm").style.display = "none";
//	document.getElementById("eCheckForm").style.display = "none";
	document.getElementById("checkMoneyOrderForm").style.display = "none";
	if (form == "echeck") {
		document.getElementById("eCheckForm").style.display = "";
	} else if (form == "checkmoneyorder") {
		document.getElementById("checkMoneyOrderForm").style.display = "";
	} else {
		document.getElementById("creditCardForm").style.display = "";
	}
}
/*
function toggleInternationalB(country) {
	if (country == "USA") {
		document.getElementById("bProvince").disabled = true;
		document.getElementById("bProvince").value = "Province N/A";
		document.getElementById("bState").disabled = false;
		document.getElementById("bState").value = "0";
	} else {
		document.getElementById("bState").disabled = true;
		document.getElementById("bState").value = "0";
		document.getElementById("bProvince").disabled = false;
		document.getElementById("bProvince").value = "";			
	}
}
function toggleInternationalS(country) {
	if (country == "USA") {
		document.getElementById("sprovince").disabled = true;
		document.getElementById("sprovince").value = "Province N/A";
		document.getElementById("sstate").disabled = false;
		document.getElementById("sstate").value = "0";
	} else {
		document.getElementById("sstate").disabled = true;
		document.getElementById("sstate").value = "0";
		document.getElementById("sprovince").disabled = false;
		document.getElementById("sprovince").value = "";			
	}
}
*/