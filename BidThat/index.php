<?php
// Filename of your index page
$index = "game.html";
// Metadata
$game = "No Tipping";
$team = "TheRons";
$instruction = <<<EOD
<p> You are given uniform, flat board which is <strong> m </strong> meters long weighing <strong> w </strong> kg. Consider it ranging from <strong>-m/2 </strong> to <strong> m/2 </strong>. We place two supports of equal heights at positions -3 and -1 and a 3 kilogram block at position -4. The No Tipping game is a two person game that works as follows: </p>

<p> <strong> Stage 1: </strong> Two players each start with <strong> k </strong> blocks, the blocks consisting of weights 1 kg through k kg (where total number of blocks is less than length of board). The players alternate placing blocks onto the board in turns until they have no blocks remaining. If after any placement, the placed block causes the board to tip, then the player who placed the block loses. If the board never tips, the game moves on to the second stage.</p>

<p> <strong> Stage 2: </strong> In this stage, players remove blocks one at a time in turns. After each play, if the block that was removed causes the board to tip, the player who removed the last block loses.</p>

<p> <strong> How is Tipping Calculated? </strong> As the game proceeds, the net torque around each support is displayed. This is computed by the weight on the board * the distance to each support (board weight included). A clockwise force represents negative torque and counterclockwise represents positive torque. For no tipping to occur, the left support must be negative and the right support must be positive.</p>

<p> <strong> Rules: </strong>
    <p> - No player may place a block on top of another</p>
    <p> - Cannot place the same weight twice </p>
    <p> - Cannot remove weight from unoccupied space </p>
</p>

<p> <strong> Instructions </strong>
    <p> - Press pop-up to access game window. </p>
    <p> - ADDING PHASE: When it is your turn (player 1), click on the weight number located near your name, and then on a 'tick' on the board to select the position to place this weight. Then click "submit move" to end your turn. </p>
    <p> - REMOVING PHASE: Select a 'tick' on the board to indicate which position you want to remove a weight from. Then click "submit move" to end your turn. </p>
</p>

<p> <strong> Note: </strong> For best experience, maximize window as much as possible. </p>
EOD;

// Size of the popup window
$width = 940;
$height = 1000;
// If your score is sortable, 1 if higher score is better, -1 if smaller score is better, 0 otherwise.
$scoring = 0;

include '../../template.php';
