jQuery(document).ready(function($) {
	//Show tooltip
	$('.xmpp-stats-chart').bind('plothover', function (event, pos, item) {
		var previousPoint = null;
		if(item) {
			if(previousPoint != item.dataIndex) {
				previousPoint = item.dataIndex;
				$('#flot-tooltip').remove();
				var time = new Date(item.datapoint[0] * 1000);
				var content = item.series.previously + item.datapoint[1] + item.series.at + time.toLocaleTimeString();
				if(window.matchMedia('(min-width: 768px)').matches) {
					$('<div id="flot-tooltip">' + content + '</div>').css({
						top: item.pageY - 16,
						left: item.pageX + 16,
					}).appendTo('body').fadeIn(200);
				}
				else {
					$('<div id="flot-tooltip" class="mobile">' + content + '</div>').css({
						top: 8,
						right: 13
					}).appendTo($(this)).fadeIn(200);
				}
			}
		} else {
			$('#flot-tooltip').remove();
			previousPoint = null;
		}
	});
	//Get data and draw graph
	$('.xmpp-stats-chart').each(function() {
		var element = $(this);
		setInterval(function get_graph() {
			$.ajax({
				url: xmpp_stats.rest_api + element.attr('data-action'),
				method: 'GET',
				timeout: 30000,
				success: function(response) {
					$.plot(element, response.data, response.options);
				}
			});
			return get_graph;
		}(), 300000);
	});
});