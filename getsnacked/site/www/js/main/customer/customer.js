function formatItem(row) {
	if (row[0] == 'productInfo' || row[0] == 'packageInfo') {
		// return row[0] on select
		// id | cost | name | availability
		row[0] = row[1];
		return "ID: " + row[1] + " | Cost: $" + row[2] + " | Name: " + row[3] + " | Status: " + row[4];
	} else if (row[0] == 'campaignInfo') {
		// id | type | name | availability
		row[0] = row[1];
		return "ID: " + row[1] + " | Type: " + row[2] + " | Name: " + row[3] + " | Access: " + row[4];
	} else if (typeof(row[1]) != 'undefined') {
		return row[1];
	} else {
		return row[0];
	}
}
$(function() {
	$('#merchantMenu').jdMenu();
	$('.editMenuOption').mouseover(function() {
		$(this).addClass('editMenuOptionOver');
	}).mouseout(function() {
		$(this).removeClass('editMenuOptionOver');
    }).click(function() {
		$('.editMenuOption').removeClass('selected');
		$(this).addClass('selected');
		$('.propertyContainer').addClass('hidden');
		$('#' + $(this).attr('id') + 'Container').removeClass('hidden');
		$('#propertyMenuItem').val($(this).attr('id'));
	});
});