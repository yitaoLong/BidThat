function setup() {
    width = document.getElementById('game-container').offsetWidth - 100;
    containerHeight = window.innerHeight;
    height = width * 3 / 4 - width / 6;
	
	canvas = createCanvas(width, Math.max(height, containerHeight * 3 / 4)); // ~4:3 aspect ratio
    canvas.parent('game-container');

    button = createButton('Next');
    button.position(width - 150, height - 50);
    button.parent('game-container');
    button.attribute('class', 'btn btn-success');
	button.attribute('id', 'next_button');
    button.mousePressed(nextTurn);
}

var value_player = [];
var budget_player = [];
var num_players = -1;
var num_rounds = -1;
var is_vikerey = 0;
var budget = -1;
var value_list = [];
var message = '';


function startGame() {
    num_players = parseInt(document.getElementById("num_players").value);
    num_rounds = parseInt(document.getElementById("num_rounds").value);
	var tmp;
    tmp = document.getElementById("vikerey_option").value;
	if(tmp == "Yes"){
		is_vikerey = 1;
	}else{
		is_vikerey = 0;
	}
	budget = parseInt(document.getElementById("budget").value);
	var input_data;
    input_data = document.getElementById('input_data').value;
	var data = input_data.split(" ");
	for(let i = 0; i < data.length; i++){
		if(data[i].length != 0){
			value_list.push(parseInt(data[i]));
		}
	}
	for(let i = 0; i < num_players; i++){
		budget_player.push(budget);
		value_player.push(0);
	}
	
	$("#num_players").attr("disabled","disabled");
	$("#num_rounds").attr("disabled","disabled");
	$("#vikerey_option").attr("disabled","disabled");
	$("#budget").attr("disabled","disabled");
	$("#input_data").attr("disabled","disabled");
	$("#game_start").attr("disabled","disabled");
	
	game = new Game();
	initial_players();
//	var x = document.getElementById('score_table').rows[1].cells;
//	x[0].innerHTML = 150;
}

function initial_players(){
	for(let i = 1; i <= num_players; i++){
		var player_div = document.createElement('div');
		player_div.style.width = '230px';
		player_div.style.display = 'inline-block';
		
		
		var la = document.createElement('label');
		la.innerHTML = "Player" + i;
		la.style.width = '50px';
		la.style.margin = '5px';
		player_div.appendChild(la);
		
		var input_area = document.createElement('input');
		input_area.setAttribute('id', 'input' + i.toString());
		input_area.style.width = '50px';
		input_area.style.margin = '5px';
		player_div.appendChild(input_area);
		
		var button = document.createElement('button');
		button.setAttribute('id', 'button' + i.toString());
		button.style.width = '60px';
		button.style.height = '30px';
		button.style.marginLeft = '5px';
		button.style.marginRight = '30px';
		button.innerHTML = 'submit';
		button.onclick = function(){
			var price = parseInt(document.getElementById('input' + i.toString()).value);
			game.player_list[i-1].price = price;
			document.getElementById('input' + i.toString()).value = 'bid';
			$("#input" + i.toString()).attr("disabled","disabled");
			$("#button" + i.toString()).attr("disabled","disabled");
		};
		player_div.appendChild(button);
		
		document.getElementById('players').appendChild(player_div);
	}
}

function nextTurn() {
	if(game.current_round == num_rounds){
		for(let i = 0; i < num_players; i++){
			if(game.player_list[i].price < game.starting_price || game.player_list[i].price > budget_player[i]){
				game.player_list[i].price = -1;
			}
		}
		
		game.current_round = 0;
		
		var highest_price = -1;
		var second_highest_price = -1;
		var buyer = -1;
		var is_pass = 0;
		var bid_price = [];
		for(let i = 0; i < num_players; i++){
			bid_price.push(game.player_list[i].price);
			if(game.player_list[i].price >= highest_price){
				second_highest_price = highest_price;
				highest_price = game.player_list[i].price;
				buyer = i;
			}
		}
		if(second_highest_price == highest_price){
			is_pass = 1;
		}
		
		if(is_pass == 1){
			game.message.update_message('This item is pass!');
		}else{
			value_player[buyer] += value_list[game.current_item-1];
			if(is_vikerey == 1){
				bid_price.sort();
				bid_price.reverse();
				if(bid_price[1] == -1){
					game.message.update_message('Player ' + (buyer+1) + ' buy this item at price 0');
				} else{
					budget_player[buyer] -= bid_price[1];
					game.message.update_message('Player ' + (buyer+1) + ' buy this item at price ' + bid_price[1]);
				}
			}else{
				budget_player[buyer] -= highest_price;
				game.message.update_message('Player ' + (buyer+1) + ' buy this item at price ' + highest_price);
			}
		}
		update_last_price();
		var x = document.getElementById('score_table').rows[buyer+1].cells;
		x[1].innerHTML = budget_player[buyer];
		x[2].innerHTML = value_player[buyer];
	} else if(game.current_round == 0){
		if(game.current_item == value_list.length){
			var highest_value = -1;
			for(let i = 0; i < num_players; i++){
				if(value_player[i] > highest_value){
					highest_value = value_player[i];
				}
			}
			var send_info = 'Winner is';
			for(let i = 0; i < num_players; i++){
				if(value_player[i] == highest_value){
					send_info += ' Player ' + (i+1);
				}
			}
			game.message.update_message(send_info);
			clear_last_price();
			$("#next_button").attr("disabled","disabled");
		}else{
			game.current_item += 1;
			game.current_round = 1;
			var send_info = "Bid for item " + game.current_item +  ". This is round 1. Starting price is 0";
			game.message.update_message(send_info);
			clear_last_price();
			for(let i = 1; i <= num_players; i++){
				document.getElementById('input' + i.toString()).value = '';
				$("#input" + i.toString()).removeAttr("disabled");
				$("#button" + i.toString()).removeAttr("disabled");
			}
		}
	}else{
		game.current_round += 1;
		
		for(let i = 0; i < num_players; i++){
			if(game.player_list[i].price < game.starting_price || game.player_list[i].price > budget_player[i]){
				game.player_list[i].price = -1;
			}
		}
		for(let i = 0; i < num_players; i++){
			if(game.player_list[i].price > game.starting_price){
				game.starting_price = game.player_list[i].price;
			}
		}
		
		var send_info = "Bid for item " + game.current_item +  ". This is round " + game.current_round + ". Starting price is " + game.starting_price;
		game.message.update_message(send_info);
		update_last_price();
		for(let i = 1; i <= num_players; i++){
			document.getElementById('input' + i.toString()).value = '';
			$("#input" + i.toString()).removeAttr("disabled");
			$("#button" + i.toString()).removeAttr("disabled");
		}
	}
}

function update_last_price(){
	for(let i = 1; i <= num_players; i++){
		var x = document.getElementById('score_table').rows[i].cells;
		if(game.player_list[i-1].price == -1){
			x[3].innerHTML = 'Invalid Bid';
		}else{
			x[3].innerHTML = game.player_list[i-1].price;
		}
	}
}

function clear_last_price(){
	for(let i = 1; i <= num_players; i++){
		var x = document.getElementById('score_table').rows[i].cells;
		x[3].innerHTML = '';
	}
}

class Player{
	
	constructor(){
		this.price = -1;
	}
}

class Scoreboard{
	
	constructor(){
		var ta = document.createElement('table');
		
		ta.setAttribute('id', 'score_table');
		ta.style.width = '800px';
  		ta.style.border = '2px solid black';
		ta.style.textAlign = 'center';
		ta.style.fontSize = '20px';
		
		var header_tr = ta.insertRow();
		var head_td1 = header_tr.insertCell();
		head_td1.appendChild(document.createTextNode('Player'));
		head_td1.style.border = '1px solid black';
		var head_td2 = header_tr.insertCell();
		head_td2.appendChild(document.createTextNode('Current Budget'));
		head_td2.style.border = '1px solid black';
		var head_td3 = header_tr.insertCell();
		head_td3.appendChild(document.createTextNode('Current Value'));
		head_td3.style.border = '1px solid black';
		var head_td4 = header_tr.insertCell();
		head_td4.appendChild(document.createTextNode("Price at Last Round"));
		head_td4.style.border = '1px solid black';
		
		
		for(let i = 0; i < num_players; i++){
			var content_tr = ta.insertRow();
			var content_td1 = content_tr.insertCell();
			content_td1.appendChild(document.createTextNode('player' + (i+1)));
			content_td1.style.border = '1px solid black';
			var content_td2 = content_tr.insertCell();
			content_td2.appendChild(document.createTextNode(budget));
			content_td2.style.border = '1px solid black';
			var content_td3 = content_tr.insertCell();
			content_td3.appendChild(document.createTextNode(0));
			content_td3.style.border = '1px solid black';
			var content_td4 = content_tr.insertCell();
			content_td4.appendChild(document.createTextNode(""));
			content_td4.style.border = '1px solid black';
		}
		
		document.getElementById('scoreboard').appendChild(ta);
	}
	
}

class Message{
	
	constructor(){
		var el = document.getElementById("auctioneer");
    	el.innerHTML="<img src=\'auctioneer.png\' width=\'200px\' height=\'200px\'>";

		this.info = "Bid for item 1. This is round 1. Starting price is 0";
		this.update_message(this.info);
	}
	
	update_message(info){
		var el = document.getElementById("message");
		el.innerHTML="<div class=\"bubble\"> <font size=\"+1\"> " + info + "</font></div>";
	}

}

class Game{
	
	constructor(){
		this.current_item = 1;
		this.current_round = 1;
		this.starting_price = 0;
		
		this.message = new Message();
		this.scoreboard = new Scoreboard();
		this.player_list = [];
		for(let i = 0; i < num_players; i++){
			var player = new Player();
			this.player_list.push(player);
		}
	}
}