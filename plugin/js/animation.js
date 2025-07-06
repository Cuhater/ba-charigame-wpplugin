document.addEventListener("DOMContentLoaded", function (event) {

	// existiert Container im DOM? wenn ja dann feuer JS
	window.addEventListener("load", function (e) {
		if (document.querySelector('.backplate')) {
			gsap.fromTo('.backplate',
				{
					rotate: 0,
				},
				{
					rotate: -6,
					duration: 2,
					ease: 'bounce.out'
				})
		}
		if (document.querySelector('#recipient-0')) {
			gsap.from("#recipient-0", {
				opacity: 0,
				y: 100,
				duration: 1,
				scrollTrigger: {
					trigger: "#recipient-0",
					start: "top 90%",
					end: "bottom 20%",
					scrub: false,
				}
			});
		}
		if (document.querySelector('#recipient-1')) {
			gsap.from("#recipient-1", {
				opacity: 0,
				y: 100,
				duration: 1,
				scrollTrigger: {
					trigger: "#recipient-1",
					start: "top 60%",
					end: "bottom 20%",
					scrub: false
				}
			});
		}
		if (document.querySelector('#recipient-2')) {
			gsap.from("#recipient-2", {
				opacity: 0,
				y: 100,
				duration: 1,
				scrollTrigger: {
					trigger: "#recipient-2",
					start: "top 30%",
					end: "bottom 20%",
					scrub: false
				}
			});
		}
	}, false);

});

