document.addEventListener('DOMContentLoaded', function() {
	setInterval(function get_stats() {
		document.querySelectorAll('.xmpp-stats').forEach(function(item) {
			//Open XHR request
			var xhr = new XMLHttpRequest();
			xhr.open('GET', xmpp_stats.rest_api + item.getAttribute('data-action'));
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
			xhr.responseType = 'json';
			xhr.onload = function() {
				// Request success
				if(this.status == 200) {
					//Check response
					if(this.response.stat===null||this.response.stat===0) {
						item.innerText = '-';
					}
					else {
						item.innerText = this.response.stat;
					}
				}
				// Request error
				else {
					item.innerText = '-';
				}
			};
			xhr.send();
		});
		return get_stats;
	}(), 300000);
});