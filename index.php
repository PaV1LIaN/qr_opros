<?php
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");


?>
<script src="/local/qr-opros/jquery-3.6.0.min.js"></script>

<body>
<div id='content'>
<div id="blocks-container">
</div>
 <button id="addBlock">Добавить лабораторию</button>
<div id="modal" style="display: none;">
	<h3>Добавить автомобиль</h3>
 <label for="nameCar">Марка транспортного средства</label> <input type="text" id="nameCar"><br>
 <br>
 <label for="typeCar">Тип автомобиля</label> <input type="text" id="typeCar"><br>
 <br>
 <label for="nomCar">Регистрационный знак автомобиля</label> <input type="text" id="nomCar"><br>
 <br>
 <button id="saveBlock">Сохранить</button> <button id="cancel">Отмена</button>
</div>
    <script>
        $(document).ready(function() {
            function loadBlocks() {
                $.ajax({
                    url: 'load_blocks.php',
                    method: 'GET',
                    success: function(data) {
                    $('#blocks-container').html(data);
                }
            });
        }

        loadBlocks();

        $('#addBlock').click(function() {
            $('#modal').show();
			$(this).hide();
        });

        $('#saveBlock').click(function() {
            var nameCar =$('#nameCar').val();
            var typeCar =$('#typeCar').val();
			var nomCar =$('#nomCar').val();

            $.ajax({
                url: 'add_block.php',
                method: 'POST',
                data: { nameCar: nameCar, typeCar: typeCar, nomCar: nomCar},
                success: function(blockData) {
					console.log(blockData);
                    var block = JSON.parse(blockData);
                    $('#blocks-container').append(`
                    <div class="block" car-id="${block.id}"> 
                        <h4>${block.nameCar}</h4>
                        <p>${block.typeCar}</p>
						<p>${block.nomCar}</p>
						<a href="application/index.php?block_id=${block.id}" class="order-btn">Скачать qr</a><br />
						<a href="application/index.php?block_id=${block.id}" class="order-btn">Тест отзыв</a><br />
                        <div class="remove-btn">Деактивировать</div>
                    </div>
                    `);
                    $('#modal').hide();
            		$('#blockTitle').val('');
            		$('#blockDescription').val('');
					$('#addBlock').show();
                }
            });
        });

        $('#cancel').click(function() {
            $('#modal').hide();
            $('#blockTitle').val('');
            $('#blockDescription').val('');
			$('#addBlock').show();
        });

        $(document).on('click', '.remove-btn', function() {
            const block = $(this).closest('.block');
            const blockId = block.data('id');

            $.ajax({
                url: 'remove_block.php',
                method: 'POST',
                data: { id: blockId },
                success: function() {
                    block.remove();
                }
            });
        });
    });
    </script>
