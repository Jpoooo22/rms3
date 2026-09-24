$(document).ready(function() {
		  function updateNotifications() {
			$.ajax({
			  url: 'notifications.php',
			  dataType: 'json',
			  success: function(data) {
				// Update the HTML with the new data
				$.each(data, function(index, item) {
				  var content = $('<div>');
				  $('#noti-img').attr('src', item.profilepic).appendTo(content);
				  $('#noti-name').text(item.notifi_name).appendTo(content);
				  $('#noti-title').text(item.notifi_title).appendTo(content);
				  $('#new-date').text(item.newDate).appendTo(content);
				  content.appendTo($('#notification'));
				});
			  },
			  error: function(jqXHR, textStatus, errorThrown) {
				console.log('Error: ' + textStatus + ' - ' + errorThrown);
			  }
			});
		  }
		  setInterval(updateNotifications, 100);
		});