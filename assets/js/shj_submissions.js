/**
 * Sharif Judge
 * @file shj_submissions.js
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 *
 *     Javascript codes for "All Submissions" and "Final Submissions" pages
 */

shj.modal_open = false;



$(document).ready(function () {
	$(document).on('click', '#select_all', function (e) {
		e.preventDefault();
		$('.code-column').selectText();
	});
	//$(".btn").click(function () {
	$("td").on('click', '.btn', function () {
		var button = $(this);
		var row = button.parents('tr');
		var type = button.data('type');
		if (type == 'download') {
			window.location = shj.site_url + 'submissions/download_file/' + row.data('u') + '/' + row.data('a') + '/' + row.data('p') + '/' + row.data('s');
			return;
		}
		var view_code_request = $.ajax({
			cache: true,
			type: 'POST',
			url: shj.site_url + 'submissions/view_code',
			data: {
				type: type,
				username: row.data('u'),
				assignment: row.data('a'),
				problem: row.data('p'),
				submit_id: row.data('s'),
				shj_csrf_token: shj.csrf_token
			},
			success: function (data) {
				if (type == 'code')
					 data.text = shj.html_encode(data.text);
				$('.modal_inside').html('<pre class="code-column">'+data.text+'</pre>');
				$('.modal_inside').prepend('<p><code>'+data.file_name+' | Submit ID: '+row.data('s')+' | Username: '+row.data('u')+' | Problem: '+row.data('p')+'</code></p>');
				if (type == 'code'){
					$('pre.code-column').snippet(data.lang, {style: shj.color_scheme});
				}
				else
					$('pre.code-column').addClass('shj_code');
			}
		});
		if (!shj.modal_open) {
			shj.modal_open = true;
			$('#shj_modal').reveal(
				{
					animationspeed: 300,
					on_close_modal: function () {
						view_code_request.abort();
					},
					on_finish_modal: function () {
						$(".modal_inside").html('<div style="text-align: center;">Loading<br><img src="'+shj.base_url+'assets/images/loading.gif"/></div>');
						shj.modal_open = false;
					}
				}
			);
		}

	});
	$(".shj_rejudge").attr('title', 'Rejudge');
	$(".shj_rejudge").click(function () {
		var row = $(this).parents('tr');
		$.ajax({
			type: 'POST',
			url: shj.site_url + 'rejudge/rejudge_single',
			data: {
				username: row.data('u'),
				assignment: row.data('a'),
				problem: row.data('p'),
				submit_id: row.data('s'),
				shj_csrf_token: shj.csrf_token
			},
			beforeSend: shj.loading_start,
			complete: shj.loading_finish,
			error: shj.loading_error,
			success: function (response) {
				if (response.done) {
					row.children('.status').html('<div class="btn pending" data-code="0">PENDING</div>');
					noty({text: 'Rejudge in progress', layout: 'bottomRight', type: 'success', timeout: 2500});
					setTimeout(update_status, update_status_interval);
				}
				else
					shj.loading_failed(response.message);
			}
		});
	});
	$(".set_final").click(
		function () {
			var row = $(this).parents('tr');
			var submit_id = row.data('s');
			var problem = row.data('p');
			var username = row.data('u');
			$.ajax({
				type: 'POST',
				url: shj.site_url + 'submissions/select',
				data: {
					submit_id: submit_id,
					problem: problem,
					username: username,
					shj_csrf_token: shj.csrf_token
				},
				beforeSend: shj.loading_start,
				complete: shj.loading_finish,
				error: shj.loading_error,
				success: function (response) {
					if (response.done) {
						$("tr[data-u='" + username + "'][data-p='" + problem + "'] i.set_final").removeClass('fa-check-circle-o color11').addClass('fa-circle-o');
						$("tr[data-u='" + username + "'][data-p='" + problem + "'][data-s='" + submit_id + "'] i.set_final").removeClass('fa-circle-o').addClass('fa-check-circle-o color11');
					}
					else
						shj.loading_failed(response.message);
				}
			});
		}
	);

	setTimeout(update_status, update_status_interval);
});


update_status_interval = 6000;
function update_status(){
	
	$('tr').each(function(){
		var status = $(this).children('.status');
		if (status.children('div').hasClass('pending')){
			$.ajax({
				type: 'POST',
				url: shj.site_url + 'submissions/status',
				data: {
					username: $(this).data('u'),
					assignment: $(this).data('a'),
					problem: $(this).data('p'),
					submit_id: $(this).data('s'),
					shj_csrf_token: shj.csrf_token
				},
				beforeSend: shj.loading_start,
				complete: shj.loading_finish,
				error: shj.loading_error,
				success: function (response) {
					response = JSON.parse(response);
					//console.log(response.status);
					//console.log(typeof response);
					var element;
					switch (response.status.toLowerCase() ){
						case 'pending':
							element = ('<div class="btn pending" data-type="result" data-code="0">PENDING</div>');
					 		noty({text: 'Still judging', layout: 'bottomRight', type: 'success', timeout: 2000});
						break;

						case  'score' :
							element = '<div class="btn ' + (response.fullmark ? 'shj-green' : 'shj-red');
							element += '" data-type="result" >' + response.final_score + '</div>';
							noty({text: 'Submission has been judged', layout: 'bottomRight', type: 'success', timeout: 2000});
						break;

						default:
							element = ('<div class="btn shj-blue" data-code="0" data-type="result">' 
															+ response.status + '</div>');
					}
					status.html(element);

					// }
					// else
					// 	shj.loading_failed(response.message);
				}
			});
		}
	});

	if ($('div.pending')[0]){
		setTimeout(update_status, update_status_interval);

	}
}