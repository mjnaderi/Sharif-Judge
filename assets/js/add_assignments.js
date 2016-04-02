
shj.row ='<tr><td>SPID</td>'
+'<td><input type="text" name="name[]" class="sharif_input short" value="Problem "/></td>'
+'<td><input type="text" name="score[]" class="sharif_input tiny2" value="100"/></td>'
+'<td><input type="text" name="c_time_limit[]" class="sharif_input tiny2" value="500"/></td>'
+'<td><input type="text" name="python_time_limit[]" class="sharif_input tiny2" value="1500"/></td>'
+'<td><input type="text" name="java_time_limit[]" class="sharif_input tiny2" value="2000"/></td>'
+'<td><input type="text" name="memory_limit[]" class="sharif_input tiny" value="50000"/></td>'
+'<input id="submit_langPID" type="hidden" name="languages[PID]" class="sharif_input short2"/>'
+'<td>'
+'	<select id="langPID" name="select_languages[PID][]" multiple class="medium">'
+'		<option value="C">C</option>'
+'		<option value="C++" selected="true">C++</option>'
+'		<option value="Python 2">Python 2</option>'
+'		<option value="Python 3">Python 3</option>'
+'		<option value="Java">Java</option>'
+'	</select>'
+'</td>'
+'<td><input type="text" name="diff_cmd[]" class="sharif_input tiny" value="diff"/></td>'
+'<td><input type="text" name="diff_arg[]" class="sharif_input tiny" value="-bB"/></td>'
+'<td><input type="checkbox" name="is_upload_only[]" class="check" value="PID"/><td><i class="fa fa-times-circle fa-lg color1 delete_problem pointer"></i></td></td>'
+'</tr>';
	$(document).ready(function(){
		$("#add").click(function(){
			$('#problems_table>tbody').append(shj.row
							.replace(/SPID/g, (shj.num_of_problems+1)) 
							.replace(/PID/g, (shj.num_of_problems))

				);
			$("select").select2();
			shj.num_of_problems++;

			$('#nop').attr('value', shj.num_of_problems);
		});
		$(document).on('click', '.delete_problem', function(){
			if (shj.num_of_problems==1) return;
			var row = $(this).parents('tr');
			row.remove();
			var i = 0;
			$('#problems_table>tbody').children('tr').each(function(){
				i++;
				$(this).children(':first').html(i);
				$(this).find('[type="checkbox"]').attr('value',i);
			});
			shj.num_of_problems--;
			$('#nop').attr('value',shj.num_of_problems);
		});
		$('#start_time').datetimepicker({
			timeFormat: 'HH:mm:ss'
		});
		$('#finish_time').datetimepicker({
			timeFormat: 'HH:mm:ss'
		});
	});


/*
	Wecode judge
	author: truongan
	date: 20160330
*/

$(document).ready(function(){
	var nop = $("[name='number_of_problems']").val();

	for (var i = 0; i < nop; i++) {
		//console.log($("#allowed_lang" + i));
		var allow_langs = $("#allowed_lang" + i).val().split(",")
		
		$("#lang" + i).val(allow_langs);
		//console.log( $("#lang" + i).val() );
	}

	$("form").submit(function(event){
		var nop = $("[name='number_of_problems']").val();

		for (var i = 0; i < nop; i++) {
			//console.log($("#lang" + i).val());
			$("#submit_lang" + i).val($("#lang" + i).val().join());
			$("#lang" + i).val(allow_langs);
		}
		//event.preventDefault();
		//return false;

	});
	$("select").select2();
});
