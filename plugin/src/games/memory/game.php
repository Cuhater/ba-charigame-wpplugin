<div id="container">
	<div id="gamesection"
		 class="mb-16">
		<div class="flex justify-center">
			<?php $memory_settings = get_field( 'memory_settings', get_the_ID() ); ?>
			<div class="memory_game_grid game mt-20"
				 data-dimension="<?php echo $memory_settings['memory_grid']; ?>"
				 data-background="<?php echo get_field('company_settings', 'option')['company-logo']; ?>">
				<div class="board-container aspect-square">
					<div class="board aspect-square"></div>
				</div>
				<div class="controls">
					<div class="stats flex justify-between w-full">
						<div class="moves">0 Karten umgedreht</div>
						<div class="timer">Zeit: 0 Sekunden</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
