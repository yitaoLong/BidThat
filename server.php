<?php
    // check command line arguments
    if($argc < 4 || !is_numeric($argv[1]) || !is_numeric($argv[2]) || !is_numeric($argv[3]) || !is_numeric($argv[4])) {
        echo("[ERROR] Please provide port number, number of players, number of rounds and Vikerey auction\n");
        echo("[ERROR] Example command: php server.php 5000 2 3 0\n");
        exit(-1);
    }

    // start server
    echo("[LOG] Starting server at localhost:$argv[1] with $argv[2] players, $argv[3] rounds and Vikerey auction marked is $argv[4]\n");

    // read data file
    $data_file = fopen("data.txt", "r") or die("Unable to open file!");
    $data = fread($data_file,filesize("data.txt"));
    fclose($data_file);
    $data = explode("\n", $data);

    $budget = (int) $data[0];
    $value_list = array();
    $count = 0;
    foreach ($data as &$value) {
        if ($count != 0){
            if (strlen($value) > 0)
                array_push($value_list, (int) $value);
        }
        $count++;
    }
    echo("[LOG] Data imported!\n");
    
    // open, bind, and begin listening on socket
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    socket_bind($socket, 'localhost', $argv[1]);
    socket_listen($socket);

    $connections;
    $observed = false;
    // wait for connections from players
    $is_websocket;
    $name;
    for($i = 1; $i <= $argv[2]; $i++) {
        // log status
        echo("[LOG] Waiting for Player $i\n");

        // blocking call waiting for connection
        $connections[$i] = socket_accept($socket);

        // do extra communication to identify client
        // if a websocket is being used we need to do a handshake
        // all other clients can send whatever they want as long as it doesn't contain "Sec-WebSocket-Key:"
        // identification code based on https://medium.com/@cn007b/super-simple-php-websocket-example-ea2cd5893575
        $identification = socket_read($connections[$i], 5000);
        if(strpos($identification, "Sec-WebSocket-Key:") !== false) {
            preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $identification, $matches);
            $key = base64_encode(pack('H*', sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $headers = "HTTP/1.1 101 Switching Protocols\r\n";
            $headers .= "Upgrade: websocket\r\n";
            $headers .= "Connection: Upgrade\r\n";
            $headers .= "Sec-WebSocket-Version: 13\r\n";
            $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
            socket_write($connections[$i], $headers, strlen($headers));
            $is_websocket[$i] = true;
            $name[$i] = "Webclient $i";

            // log connection
            echo("[LOG] Player $i connected via websocket\n\n");
        } else {
            $is_websocket[$i] = false;

            $name[$i] = str_replace(array(" ", "\r", "\n"), '', $identification);


            // log connection
            echo("[LOG] Player $i connected via TCP\n\n");
        }
    }

    // send a message to a client over a socket or websocket
    function send_message($client, $message, $is_web) {
        if($is_web) {
            socket_write($client, chr(129) .  chr(strlen($message)) . $message);
        } else {
            socket_send($client, $message, strlen($message), 0);
        }
    }

    // compliant masking and decoding based on https://gist.github.com/dg/6205452
    function web_decode($frame) {
        $decoded_frame = "";
        for ($i = 6; $i < strlen($frame); $i++) {
            $decoded_frame .= $frame[$i] ^ $frame[2 + ($i - 2) % 4];
        }
        return $decoded_frame;
    }

    // initialize game
    $num_players = $argv[2];
    $num_rounds = $argv[3];
    $is_vikerey = $argv[4];
    $budget_player;
    $value_player;
    $is_lose;
    $remaining_players = $num_players;
    for($i = 1; $i <= $num_players; $i++){
        $budget_player[$i] = $budget;
        $value_player[$i] = 0;
        $is_lose[$i] = 0;
    }

    // send initial data to all the players
    for($i = 1; $i <= $num_players; $i++){
        $tmp = implode(" ", $value_list);
        send_message($connections[$i], "$tmp $num_rounds $is_vikerey $budget $i\n", $is_websocket[$i]);
    }
    
    // TODO: send initial data to observer

    // both players now have 2 minutes each remaining (120 seconds)
    $time_remaining;
    for($i = 1; $i <= $num_players; $i++){
        $time_remaining[$i] = 120 * 1000000000;
    }
    $time_start = microtime(true);

    // play game
    //  for loop for each item
    for($item = 1; $item <= count($value_list); $item++){
        // print status
        echo("[LOG] Bid for item $item\n");
        
        // bid of each player at a round
        $bid_price;
        for($p = 0; $p <= $num_players; $p++){
            $bid_price[$p] = -1;
        }
        $starting_price = 0;
        // for loop for each round
        for($round=1; $round <= $num_rounds; $round++){
            echo("[LOG] Round $round for item $item: Starting price is $starting_price\n");
            // for loop for each player
            for($i = 1; $i <= $num_players; $i++){
                // ignore player if it is out
                if ($is_lose[$i] == 1){
                    echo("[LOG] Player $i is out\n");
                    continue;
                }
                echo("[LOG] Waiting for Player $i to send a command\n");
                $print_time = $time_remaining[$i] / 1000000000.0;
                echo("[INFO] $print_time seconds remaining\n\n");
                
                // blocking operation waiting for command
                // set a timeout on this operation
                // we will be nice here and round up to account for latency.
                // Ex. if a player has 73.4 seconds remaining, we will give them 74
                socket_set_option($connections[$i], SOL_SOCKET, SO_RCVTIMEO, array('sec' => intval($time_remaining[$i] / 1000000000), 'usec'=> 0));
                $command = socket_read($connections[$i], 1024);
                
                // in the event of a timeout, forcefully end the game of current player, but continue the game with remaining players
                if(!$command) {
                    // send messages to players and close socket
                    send_message($connections[$i], "end\n", $is_websocket[$i]);
                    // log results
                    echo("[LOG] Player $i timeout\n");
                    // player i is lose
                    $is_lose[$i] = 1;
                    $bid_price[$i] = -1;
                    $remaining_players--;
                    // if there has only one player, then this player win the game directly
                    if($remaining_players == 1){
                        for($j = 1; $j <= $num_players; $j++){
                            if($is_lose[$j] == 0){
                                echo("[INFO] Player $j wins!\n\n");
                                // exit program
                                exit;
                            }
                        }
                    }
                    $time_start = microtime(true);
                    continue;
                }
                
                // if coming from a websocket, decode recieved packet
                if($is_websocket[$i]) {
                    $command = web_decode($command);
                }
                
                // split and interpret command
                $command = str_replace(array("\r", "\n"), '', $command);
                $command_parts = explode(" ", $command);
                
                //  record bid price
                if((int)$command_parts[0] < $starting_price || (int)$command_parts[0] > $budget_player[$i]){
                    $bid_price[$i] = -1;
                } else {
                    $bid_price[$i] = (int)$command_parts[0];
                }
                
                $time_remaining[$i] -= microtime(true) - $time_start;
                $time_start = microtime(true);
            }
            
            // print information and send bid price of this round to each player
            echo("Bid price at this round\n");
            for($p = 1; $p <= $num_players; $p++){
                $tmp = implode(" ", $bid_price);
                send_message($connections[$p], "bid $tmp\n", $is_websocket[$p]);
                echo("Player $p offer a price of $bid_price[$p]\n");
            }
            $starting_price = max($bid_price);
        }
        
        // the player who offer the highest price get the item, if there has two or more players offer the same highest price, then this item passes.
        $highest_price = -1;
        $second_highest_price = -1;
        $buyer = -1;
        $is_pass = 0;
        for($i = 1; $i <= $num_players; $i++){
            if($bid_price[$i] >= $highest_price){
                $second_highest_price = $highest_price;
                $highest_price = $bid_price[$i];
                $buyer = $i;
            }
        }

        if($second_highest_price == $highest_price){
            $is_pass = 1;
        }
        if($is_pass == 1){
            echo("[INFO] This item is pass\n");
        }else {
            $value_player[$buyer] += $value_list[$item-1];
            // whether is Vikerey auction
            if($is_vikerey == 1){
                rsort($bid_price);
                if($bid_price[1] == -1){
                    echo("[INFO] Player $buyer buy item $item at price of 0\n");
                }else{
                    $budget_player[$buyer] -= $bid_price[1];
                    echo("[INFO] Player $buyer buy item $item at price of $bid_price[1]\n");
                }
            }else{
                $budget_player[$buyer] -= $highest_price;
                echo("[INFO] Player $buyer buy item $item at price of $highest_price\n");
            }
        }
        
        
        $info = "";
        //  print statistics and send info to players
        for($i = 1; $i <= $num_players; $i++){
            echo("Player $i: current budget $budget_player[$i], current value $value_player[$i]\n");
            if($i == $num_players){
                $info .= "$i $budget_player[$i] $value_player[$i]";
            }else{
                $info .= "$i $budget_player[$i] $value_player[$i] ";
            }
        }
        for($i = 1; $i <= $num_players; $i++){
            $command = socket_read($connections[$i], 1024);
            send_message($connections[$i], "result $info\n", $is_websocket[$i]);
        }
    }

//    // send both players 0
//    // useful for graceful quitting
//    for($i = 1; $i <= $num_players; $i++){
//        send_message($connections[$i], "end\n", $is_websocket[$i]);
//    }

    // print result
    $highest_value = 0;
    for($i = 1; $i <= $num_players; $i++){
        if($value_player[$i] > $highest_value){
            $highest_value = $value_player[$i];
        }
    }
    for($i = 1; $i <= $num_players; $i++){
        if($value_player[$i] == $highest_value){
            echo("[INFO] Winner is player $i\n");
        }
    }
?>
