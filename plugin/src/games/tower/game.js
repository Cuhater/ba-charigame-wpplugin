"use strict";

//console.clear();
class Stage {
	constructor() {
		// container
		this.render = function () {
			this.renderer.render(this.scene, this.camera);
		};
		this.add = function (elem) {
			this.scene.add(elem);
		};
		this.remove = function (elem) {
			this.scene.remove(elem);
		};
		this.container = document.getElementById('game');
		// renderer
		this.renderer = new THREE.WebGLRenderer({
			antialias: true,
			alpha: false
		});
		this.highscore = 0;
		this.renderer.setSize(window.innerWidth, window.innerHeight);
		this.renderer.setClearColor('#FFFFFF', 1);
		if (this.container) {
			this.container.appendChild(this.renderer.domElement);
		}
		// scene
		this.scene = new THREE.Scene();
		// camera
		let aspect = window.innerWidth / window.innerHeight;
		let d = 20;
		this.camera = new THREE.OrthographicCamera(-d * aspect, d * aspect, d, -d, -100, 1000);
		this.camera.position.x = 2;
		this.camera.position.y = 2;
		this.camera.position.z = 2;
		this.camera.lookAt(new THREE.Vector3(0, 0, 0));
		//light
		this.light = new THREE.DirectionalLight(0xffffff, 0.5);
		this.light.position.set(0, 499, 0);
		this.scene.add(this.light);
		this.softLight = new THREE.AmbientLight(0xffffff, 0.4);
		this.scene.add(this.softLight);
		window.addEventListener('resize', () => this.onResize());
		this.onResize();
	}

	setCamera(y, speed = 0.3) {
		gsap.to(this.camera.position, speed, {y: y + 4, ease: Power1.easeInOut});
		gsap.to(this.camera.lookAt, speed, {y: y, ease: Power1.easeInOut});
	}

	onResize() {
		let viewSize = 30;
		this.renderer.setSize(window.innerWidth, window.innerHeight);
		this.camera.left = window.innerWidth / -viewSize;
		this.camera.right = window.innerWidth / viewSize;
		this.camera.top = window.innerHeight / viewSize;
		this.camera.bottom = window.innerHeight / -viewSize;
		this.camera.updateProjectionMatrix();
	}
}

class Block {
	constructor(block) {
		// set size and position
		this.STATES = {ACTIVE: 'active', STOPPED: 'stopped', MISSED: 'missed'};
		this.MOVE_AMOUNT = 12;
		this.dimension = {width: 0, height: 0, depth: 0};
		this.position = {x: 0, y: 0, z: 0};
		this.targetBlock = block;
		this.index = (this.targetBlock ? this.targetBlock.index : 0) + 1;
		this.workingPlane = this.index % 2 ? 'x' : 'z';
		this.workingDimension = this.index % 2 ? 'width' : 'depth';
		// set the dimensions from the target block, or defaults.
		this.dimension.width = this.targetBlock ? this.targetBlock.dimension.width : 10;
		this.dimension.height = this.targetBlock ? this.targetBlock.dimension.height : 2;
		this.dimension.depth = this.targetBlock ? this.targetBlock.dimension.depth : 10;
		this.position.x = this.targetBlock ? this.targetBlock.position.x : 0;
		this.position.y = this.dimension.height * this.index;
		this.position.z = this.targetBlock ? this.targetBlock.position.z : 0;
		this.colorOffset = this.targetBlock ? this.targetBlock.colorOffset : Math.round(Math.random() * 100);
		// set color
		if (!this.targetBlock) {
			this.color = 0x333344;
		} else {
			let offset = this.index + this.colorOffset;
			var r = Math.sin(0.3 * offset) * 55 + 200;
			var g = Math.sin(0.3 * offset + 2) * 55 + 200;
			var b = Math.sin(0.3 * offset + 4) * 55 + 200;
			this.color = new THREE.Color(r / 255, g / 255, b / 255);
		}
		// state
		this.state = this.index > 1 ? this.STATES.ACTIVE : this.STATES.STOPPED;
		// set direction
		this.speed = -0.1 - (this.index * 0.005);
		if (this.speed < -4)
			this.speed = -4;
		this.direction = this.speed;

		let materials;
		if (this.index == 1) {
			const textureLoader = new THREE.TextureLoader();
			const topTexture = textureLoader.load(game_vars.logo);  // Ersetze 'path_to_your_image.jpg' durch den Pfad zu deinem Bild

			const sideMaterial = new THREE.MeshStandardMaterial({color: this.color, flatShading: THREE.FlatShading});
			const topMaterial = new THREE.MeshStandardMaterial({map: topTexture, flatShading: THREE.FlatShading});

			materials = [
				sideMaterial, // right
				sideMaterial, // left
				topMaterial,  // top
				sideMaterial, // bottom
				sideMaterial, // front
				sideMaterial  // back
			];
		} else {
			this.material = new THREE.MeshStandardMaterial({color: this.color, flatShading: THREE.FlatShading});
			materials = this.material;
		}

		// create block
		let geometry = new THREE.BoxGeometry(this.dimension.width, this.dimension.height, this.dimension.depth);
		geometry.applyMatrix4(new THREE.Matrix4().makeTranslation(this.dimension.width / 2, this.dimension.height / 2, this.dimension.depth / 2));
		this.mesh = new THREE.Mesh(geometry, materials);
		this.mesh.position.set(this.position.x, this.position.y + (this.state == this.STATES.ACTIVE ? 0 : 0), this.position.z);
		if (this.state == this.STATES.ACTIVE) {
			this.position[this.workingPlane] = Math.random() > 0.5 ? -this.MOVE_AMOUNT : this.MOVE_AMOUNT;
		}
	}

	reverseDirection() {
		this.direction = this.direction > 0 ? this.speed : Math.abs(this.speed);
	}

	place() {
		this.state = this.STATES.STOPPED;
		let overlap = this.targetBlock.dimension[this.workingDimension] - Math.abs(this.position[this.workingPlane] - this.targetBlock.position[this.workingPlane]);
		let blocksToReturn = {
			plane: this.workingPlane,
			direction: this.direction
		};
		if (this.dimension[this.workingDimension] - overlap < 0.3) {
			overlap = this.dimension[this.workingDimension];
			blocksToReturn.bonus = true;
			this.position.x = this.targetBlock.position.x;
			this.position.z = this.targetBlock.position.z;
			this.dimension.width = this.targetBlock.dimension.width;
			this.dimension.depth = this.targetBlock.dimension.depth;
		}
		if (overlap > 0) {
			let choppedDimensions = {width: this.dimension.width, height: this.dimension.height, depth: this.dimension.depth};
			choppedDimensions[this.workingDimension] -= overlap;
			this.dimension[this.workingDimension] = overlap;
			let placedGeometry = new THREE.BoxGeometry(this.dimension.width, this.dimension.height, this.dimension.depth);
			placedGeometry.applyMatrix4(new THREE.Matrix4().makeTranslation(this.dimension.width / 2, this.dimension.height / 2, this.dimension.depth / 2));
			let placedMesh = new THREE.Mesh(placedGeometry, this.material);
			let choppedGeometry = new THREE.BoxGeometry(choppedDimensions.width, choppedDimensions.height, choppedDimensions.depth);
			choppedGeometry.applyMatrix4(new THREE.Matrix4().makeTranslation(choppedDimensions.width / 2, choppedDimensions.height / 2, choppedDimensions.depth / 2));
			let choppedMesh = new THREE.Mesh(choppedGeometry, this.material);
			let choppedPosition = {
				x: this.position.x,
				y: this.position.y,
				z: this.position.z
			};
			if (this.position[this.workingPlane] < this.targetBlock.position[this.workingPlane]) {
				this.position[this.workingPlane] = this.targetBlock.position[this.workingPlane];
			} else {
				choppedPosition[this.workingPlane] += overlap;
			}
			placedMesh.position.set(this.position.x, this.position.y, this.position.z);
			choppedMesh.position.set(choppedPosition.x, choppedPosition.y, choppedPosition.z);
			blocksToReturn.placed = placedMesh;
			if (!blocksToReturn.bonus)
				blocksToReturn.chopped = choppedMesh;
		} else {
			this.state = this.STATES.MISSED;
		}
		this.dimension[this.workingDimension] = overlap;
		return blocksToReturn;
	}

	tick() {
		if (this.state == this.STATES.ACTIVE) {
			let value = this.position[this.workingPlane];
			if (value > this.MOVE_AMOUNT || value < -this.MOVE_AMOUNT)
				this.reverseDirection();
			this.position[this.workingPlane] += this.direction;
			this.mesh.position[this.workingPlane] = this.position[this.workingPlane];
		}
	}
}

class Game {
	constructor() {
		//Set these states to Game-Container
		this.STATES = {
			'LOADING': 'loading',
			'PLAYING': 'playing',
			'READY': 'ready',
			'ENDED': 'ended',
			'RESETTING': 'resetting'
		};
		this.blocks = [];
		this.state = this.STATES.LOADING;
		this.stage = new Stage();
		this.mainContainer = document.getElementById('container');
		this.scoreContainer = document.getElementById('score');
		this.startButton = document.getElementById('start-button');
		this.instructions = document.getElementById('instructions');
		this.playAgain = document.getElementById('btn-play-again');
		this.timer = document.getElementById('timer');
		if (this.scoreContainer) {
			this.scoreContainer.innerHTML = '0';
		}
		this.newBlocks = new THREE.Group();
		this.placedBlocks = new THREE.Group();
		this.choppedBlocks = new THREE.Group();
		this.stage.add(this.newBlocks);
		this.stage.add(this.placedBlocks);
		this.stage.add(this.choppedBlocks);
		this.addBlock();
		this.tick();
		this.updateState(this.STATES.READY);
		document.addEventListener('keydown', e => {
			if (e.keyCode == 32)
				this.onAction();
		});
		if (this.startButton) {
			this.startButton.addEventListener("click", e => {
				document.addEventListener('click', e => {
					this.onAction();
				});
			});
		}

		if (this.playAgain) {
			this.playAgain.addEventListener("click", e => {
				let modal = document.getElementById('modal-game-end')
				modal.classList.add('hidden');
				window.game.restartGame();
			});
		}

		// document.document.addEventListener('touchstart', e => {
		// 	e.preventDefault();
		// 	// this.onAction();
		// 	// ☝️ this triggers after click on android so you
		// 	// insta-lose, will figure it out later.
		// });
	}

	startTimer() {
		clearInterval(this.loop);
		this.timer.style.opacity = "1";
		this.totalTime = 0;
		this.loop = setInterval(() => {
			this.totalTime++
			this.timer.innerText = `Zeit: ${this.totalTime} Sekunden`
		}, 1000)
	}

	updateState(newState) {
		for (let key in this.STATES)
			if (this.mainContainer) {
				this.mainContainer.classList.remove(this.STATES[key]);
				this.mainContainer.classList.add(newState);
				this.state = newState;
			}

	}

	onAction() {
		switch (this.state) {
			case this.STATES.READY:
				this.startGame();
				this.startTimer();
				document.getElementById('score').style.opacity = "1";
				this.instructions.style.opacity = "0";
				break;
			case this.STATES.PLAYING:
				this.placeBlock();
				break;
			case this.STATES.ENDED:

				//this.restartGame();
				break;
		}
	}

	startGame() {
		if (this.state != this.STATES.PLAYING) {
			if (this.scoreContainer) {
				this.scoreContainer.innerHTML = '0';
			}
			this.updateState(this.STATES.PLAYING);
			this.addBlock();
		}
	}

	restartGame() {
		this.updateState(this.STATES.RESETTING);
		this.startTimer();
		let oldBlocks = this.placedBlocks.children;
		let removeSpeed = 0.2;
		let delayAmount = 0.02;
		for (let i = 0; i < oldBlocks.length; i++) {
			gsap.to(oldBlocks[i].scale, removeSpeed, {
				x: 0,
				y: 0,
				z: 0,
				delay: (oldBlocks.length - i) * delayAmount,
				ease: Power1.easeIn,
				onComplete: () => this.placedBlocks.remove(oldBlocks[i])
			});
			gsap.to(oldBlocks[i].rotation, removeSpeed, {y: 0.5, delay: (oldBlocks.length - i) * delayAmount, ease: Power1.easeIn});
		}
		let cameraMoveSpeed = removeSpeed * 2 + (oldBlocks.length * delayAmount);
		this.stage.setCamera(2, cameraMoveSpeed);
		let countdown = {value: this.blocks.length - 1};
		gsap.to(countdown, cameraMoveSpeed, {
			value: 0, onUpdate: () => {
				this.scoreContainer.innerHTML = String(Math.round(countdown.value));
			}
		});
		this.blocks = this.blocks.slice(0, 1);
		setTimeout(() => {
			this.startGame();
		}, cameraMoveSpeed * 1000);
	}

	placeBlock() {
		let currentBlock = this.blocks[this.blocks.length - 1];
		let newBlocks = currentBlock.place();
		this.newBlocks.remove(currentBlock.mesh);
		if (newBlocks.placed)
			this.placedBlocks.add(newBlocks.placed);
		if (newBlocks.chopped) {
			this.choppedBlocks.add(newBlocks.chopped);
			let positionParams = {y: '-=30', ease: Power1.easeIn, onComplete: () => this.choppedBlocks.remove(newBlocks.chopped)};
			let rotateRandomness = 10;
			let rotationParams = {
				delay: 0.05,
				x: newBlocks.plane == 'z' ? ((Math.random() * rotateRandomness) - (rotateRandomness / 2)) : 0.1,
				z: newBlocks.plane == 'x' ? ((Math.random() * rotateRandomness) - (rotateRandomness / 2)) : 0.1,
				y: Math.random() * 0.1,
			};
			if (newBlocks.chopped.position[newBlocks.plane] > newBlocks.placed.position[newBlocks.plane]) {
				positionParams[newBlocks.plane] = '+=' + (40 * Math.abs(newBlocks.direction));
			} else {
				positionParams[newBlocks.plane] = '-=' + (40 * Math.abs(newBlocks.direction));
			}
			gsap.to(newBlocks.chopped.position, 1, positionParams);
			gsap.to(newBlocks.chopped.rotation, 1, rotationParams);
		}
		this.addBlock();
	}

	addBlock() {
		let lastBlock = this.blocks[this.blocks.length - 1];
		if (lastBlock && lastBlock.state == lastBlock.STATES.MISSED) {
			return this.endGame();
		}
		if (this.scoreContainer) {
			this.scoreContainer.innerHTML = String(this.blocks.length - 1);
		}
		let newKidOnTheBlock = new Block(lastBlock);
		this.newBlocks.add(newKidOnTheBlock.mesh);
		this.blocks.push(newKidOnTheBlock);
		this.stage.setCamera(this.blocks.length * 2);
		if (this.blocks.length >= 5)
			this.instructions.classList.add('hide');
	}

	endGame() {
		this.updateState(this.STATES.ENDED);
		if(this.scoreContainer.innerHTML > 1) {
			document.getElementById('game-objectives').innerHTML = "Punkte erzielt";
		}
		else{
			document.getElementById('game-objectives').innerHTML = "Punkt erzielt";
		}

		document.getElementById('game-points').innerHTML = this.scoreContainer.innerHTML;
		document.getElementById('game-points-end').innerHTML = this.scoreContainer.innerHTML;
		document.getElementById('game-time').innerHTML = this.totalTime;
		if(this.totalTime > 1) {
			     document.getElementById('game-time-unit').innerHTML = 'Sekunden';
		}
		else{
					document.getElementById('game-time-unit').innerHTML = 'Sekunde';
		}
		document.getElementById('modal-game-end').classList.remove('hidden');

		let totalBonus = setTotalBonus(parseInt(this.scoreContainer.innerHTML));
		this.highscore = this.scoreContainer.innerHTML;

		if(totalBonus < 1){
			document.getElementById('show-donation-triangle').classList.add('hidden');
			document.getElementById('not-scored').classList.remove('hidden');
			document.getElementById('scored').classList.add('hidden');
		}
		else {
			document.getElementById('show-donation-triangle').classList.remove('hidden');
			document.getElementById('not-scored').classList.add('hidden');
			document.getElementById('scored').classList.remove('hidden');
		}
		console.log("HALLO");
		console.log(this.highscore);
		let formattedNumber = totalBonus.toFixed(2).replace('.', ',');
		document.getElementById('personal-bonus').innerHTML = formattedNumber;
	}

	tick() {
		this.blocks[this.blocks.length - 1].tick();
		this.stage.render();
		requestAnimationFrame(() => {
			this.tick();
		});
	}
}

document.addEventListener('DOMContentLoaded', () => {
	window.game = new Game();
});
//prevent scrolling with spacebar
window.addEventListener('keydown', function (e) {
	if (e.keyCode == 32 && e.target == document.body) {
		e.preventDefault();
	}
});
