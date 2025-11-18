// Menu toggle
document.addEventListener('DOMContentLoaded', function(){
  var btn = document.getElementById('menu-toggle');
  var menu = document.getElementById('menu');
  if(btn){
    btn.addEventListener('click', function(){
      menu.classList.toggle('show');
    });
  }

  // Carousel
  var carousel = document.querySelector('.carousel .slides');
  if(carousel){
    var slides = carousel.children;
    var total = slides.length;
    var index = 0;
    var dotsWrap = document.getElementById('carousel-dots');
    var prev = document.querySelector('.carousel-prev');
    var next = document.querySelector('.carousel-next');
    var interval = null;

    function goTo(i){
      index = (i + total) % total;
      carousel.style.transform = 'translateX(' + (-index * 100) + '%)';
      updateDots();
    }

    function updateDots(){
      if(!dotsWrap) return;
      dotsWrap.innerHTML = '';
      for(var i=0;i<total;i++){
        var b = document.createElement('button');
        if(i === index) b.classList.add('active');
        (function(i){
          b.addEventListener('click', function(){ goTo(i); stopAuto(); });
        })(i);
        dotsWrap.appendChild(b);
      }
    }

    function nextSlide(){ goTo(index + 1); }
    function prevSlide(){ goTo(index - 1); }

    if(next) next.addEventListener('click', function(){ nextSlide(); stopAuto(); });
    if(prev) prev.addEventListener('click', function(){ prevSlide(); stopAuto(); });

    function startAuto(){ interval = setInterval(nextSlide, 5000); }
    function stopAuto(){ if(interval) clearInterval(interval); }

    updateDots();
    startAuto();

    // pause on hover
    var hero = document.querySelector('.hero');
    if(hero){
      hero.addEventListener('mouseenter', stopAuto);
      hero.addEventListener('mouseleave', startAuto);
    }
  }
});
