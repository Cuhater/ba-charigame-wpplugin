const setTotalBonus = (gameScore) => {
	let donation_dist = helper_vars.dist;
	let donation_type = helper_vars.gametype;
	if(!donation_type){
		for (let i = 0; i < donation_dist.length; i++) {
			if (gameScore >= donation_dist[i].limit) {
				return parseInt(donation_dist[i].spendenbetrag);
			}
		}
		if (gameScore < donation_dist[donation_dist.length - 1].limit) {
			return 0;
		}
	}
	else{
		for (let i = 0; i < donation_dist.length; i++) {
			if (gameScore <= donation_dist[i].limit) {
				return parseInt(donation_dist[i].spendenbetrag);
			}
		}
		if (gameScore > donation_dist[donation_dist.length - 1].limit) {
			return 0;
		}
	}

}

function LightenDarkenColor(col, amt) {

	var usePound = false;

	if (col[0] == "#") {
		col = col.slice(1);
		usePound = true;
	}

	var num = parseInt(col, 16);

	var r = (num >> 16) + amt;

	if (r > 255) r = 255;
	else if (r < 0) r = 0;

	var b = ((num >> 8) & 0x00FF) + amt;

	if (b > 255) b = 255;
	else if (b < 0) b = 0;

	var g = (num & 0x0000FF) + amt;

	if (g > 255) g = 255;
	else if (g < 0) g = 0;

	return (usePound ? "#" : "") + (g | (b << 8) | (r << 16)).toString(16);

}

const createDonationTriangle = () => {
	let recipients = helper_vars.recipients;
	$('.picker').trianglePicker({
		polygon: {
			width: null,
			fillColor: LightenDarkenColor(helper_vars.teritary_color, -20),
			line: {
				width: 1,
				color: 'white',
				centerLines: true,
				centerLineWidth: null
			}
		},
		handle: {
			color: '#BF1D1D',
			backgroundImage: helper_vars.logo,
			width: null,
			height: null,
			borderRadius: null
		},
		inputs: {
			bottomRight: {
				//name: 'Stiftung Wilderness International',
				name: recipients.recipient_2.title,
				id: 'score[]',
				class: ''
			},
			topMiddle: {
				//name: 'David Sheldrick Wildlife Trust',
				name: recipients.recipient_3.title,
				id: 'score[]',
				class: ''
			},
			bottomLeft: {
				//name: 'Mission Erde e.V.',
				name: recipients.recipient_1.title,
				id: 'score[]',
				class: ''
			},
			decimalPlaces: 0
		}
	}, function (name, values) {
		$('.topMiddleLabel').html(recipients.recipient_3.title + ' <span class="font-main text-secondary">' + values[recipients.recipient_3.title].toFixed(2) + ' %</span>')
		$('.bottomLeft').html(recipients.recipient_1.title + ' <span class="font-main text-secondary">' + values[recipients.recipient_1.title].toFixed(2) + ' %</span>')
		$('.bottomRight').html(recipients.recipient_2.title + ' <span class="font-main text-secondary">' + values[recipients.recipient_2.title].toFixed(2) + ' %</span>')
	})
}

const distributeDonation = () => {

	document.getElementById('bottom-seperator').classList.add('hidden');
	document.getElementById('gamesection-headline').classList.remove('-mt-8');
	document.getElementById('gamesection-headline').classList.add('mt-8');
	//document.getElementById('spiel').classList.add('mt-8');
	document.getElementById('spiel').classList.remove('bg-secondary');
	document.getElementById('gamesection-headline').classList.add('text-secondary');
	document.getElementById('gamesection-headline').classList.remove('text-white');
	document.getElementById('intro').classList.add('hidden');
	document.getElementById('container').classList.add('hidden');
	document.getElementById('how-to-play').classList.add('hidden');
	document.getElementById('modal-game-end').classList.add('hidden');
	document.getElementById('btn-submit-score').classList.remove('hidden');
	document.getElementById('picker-container').classList.remove('hidden');
	document.getElementById('picker-container').classList.add('py-12');
	document.getElementById('picker').classList.remove('hidden');
	document.getElementById('gamesection-headline').innerHTML = 'Spende verteilen';


	createDonationTriangle();

	$('html, body').animate({
		scrollTop: $('#picker').offset().top - 50

	}, 1000);

	//document.getElementById('triangle-picker-handle').style.backgroundImage = "url(" + helper_vars.logo + ")";
}

const playAgain = () => {
	window.onkeydown = function (e) {
		return !(e.keyCode == 32);
	};

	document.getElementById('intro').classList.remove('hidden');
	document.getElementById('container').classList.remove('hidden');
	document.getElementById('how-to-play').classList.remove('hidden');
	document.getElementById('spiel').classList.remove('hidden');
	document.getElementById('spiel').classList.remove('mt-8');
	document.getElementById('btn-submit-score').classList.add('hidden');
	document.getElementById('cta-end').classList.toggle('hidden');
	document.getElementById('btn-play-again-end').classList.add('hidden');
	document.getElementById('btn-play-again-end-container').classList.add('hidden');
	document.getElementById('gamesection-headline').innerHTML = 'Das Spiel';
	$('html, body').animate({
		scrollTop: $('#spiel').offset().top + 200
	}, 1000);

	window.game.restartGame();
}

const updateHighscore = () => {

	let gameCode = document.getElementById('hidden-code').dataset.gameCode;
	const nonce = helper_vars.plugin_path.gamenonce;
	let scoreElements = document.querySelectorAll('input[name="score[]"]');


	let lastPlayedUTC = new Date();
	lastPlayedUTC.setHours(lastPlayedUTC.getHours() + 1);
	let lastPlayed = lastPlayedUTC.getTime();
	document.getElementById('gamesection-headline').innerHTML = 'Sie möchten es noch mal versuchen?';
	document.getElementById('btn-play-again-end-container').classList.remove('hidden');
	document.getElementById('btn-submit-score').classList.toggle('hidden');
	document.getElementById('cta-end').classList.toggle('hidden');
	document.getElementById('picker').classList.add('hidden');
	document.getElementById('btn-play-again-end').classList.remove('hidden');
	document.getElementById('recipients').classList.add('hidden');
	document.getElementById('picker-container').classList.add('hidden');
	document.getElementById('picker-container').classList.remove('py-12');

	jQuery('html, body').animate({
		scrollTop: $('body').offset().top
	}, 1000);

	jQuery.ajax({
		url: myAjax.ajaxurl,
		type: 'POST',
		dataType: 'json',
		data: {
			action: 'set_user_highscore_db',
			nonce: nonce,
			last_played: lastPlayed,
			highscore: this.game.highscore,
			code: gameCode,
			recipient_1: scoreElements[0].value,
			recipient_2: scoreElements[1].value,
			recipient_3: scoreElements[2].value,
		},
		success: function (response) {
			let campaignId = document.getElementById('main').dataset.campaign;
			jQuery.ajax({
				url: myAjax.ajaxurl,
				type: 'POST',
				data: {
					action: 'update_donations',
					campaign_id: campaignId,
				},
				success: function (response) {

					if (response.success) {
						updateDonationDisplay(response.data);

					} else {
						console.error(response.data.message);
					}
				},
				error: function (xhr, status, error) {
					console.error('AJAX error:', error);
				},
			});

		},
		error: function (xhr, status, error) {
			console.error('AJAX Error:', error);
		}
	});


};


function updateDonationDisplay(donations) {

	let donationSum = 0;
	let scoreOne = 0;
	let scoreTwo = 0;
	let scoreThree = 0;
	donations.forEach(donation => {
		donationSum += donation.score_r1 + donation.score_r2 + donation.score_r3;
		scoreOne += donation.score_r1;
		scoreTwo += donation.score_r2;
		scoreThree += donation.score_r3;
	});
	const donationContainer = document.getElementById('donation-display');
	donationContainer.innerHTML = donationSum.toFixed(2);

	// Dynamische Breite der Balken setzen
	const barOne = document.getElementById('recipient-bar-0');
	const barTwo = document.getElementById('recipient-bar-1');
	const barThree = document.getElementById('recipient-bar-2');

	const numOne = document.getElementById('recipient-num-0');
	const numTwo = document.getElementById('recipient-num-1');
	const numThree = document.getElementById('recipient-num-2');

	if (donationSum > 0) {
		barOne.style.width = `${(scoreOne / donationSum) * 100}%`;
		barTwo.style.width = `${(scoreTwo / donationSum) * 100}%`;
		barThree.style.width = `${(scoreThree / donationSum) * 100}%`;
		numOne.innerHTML = `${((scoreOne / donationSum) * 100).toFixed(0)}%`;

		numTwo.innerHTML = `${((scoreTwo / donationSum) * 100).toFixed(0)}%`;

		numThree.innerHTML = `${((scoreThree / donationSum) * 100).toFixed(0)}%`;

	} else {
		numOne.style.width = '0%';
		numTwo.style.width = '0%';
		numThree.style.width = '0%';
		numOne.innerHTML = '0%';
		numTwo.innerHTML ='0%';
		numThree.innerHTML ='0%';

	}
}

document.addEventListener('DOMContentLoaded', () => {
	const button = document.getElementById('show-donation-triangle');
	const distribute = document.getElementById('btn-submit-score');
	const playAgainEnd = document.getElementById('btn-play-again-end');

	if (button) {
		button.addEventListener('click', distributeDonation);
	}
	if (distribute) {
		distribute.addEventListener('click', updateHighscore);
	}
	if (playAgainEnd) {
		playAgainEnd.addEventListener('click', playAgain);
	}

	let confetti = new Confetti('btn-submit-score');
	confetti.setCount(2500);
	confetti.setSize(1);
	confetti.setPower(35);
	confetti.setFade(false);
	confetti.setColors([LightenDarkenColor(helper_vars.secondary_color, 20), LightenDarkenColor(helper_vars.primary_color, 20), "#ffffff"]);
	confetti.destroyTarget(false);

	//TODO: Smooth scroll nicer implement here
	document.querySelector('a[href="#spiel"]').addEventListener('click', function(event) {
		event.preventDefault();
		document.querySelector('#spiel').scrollIntoView({ behavior: 'smooth' });
	});

});
