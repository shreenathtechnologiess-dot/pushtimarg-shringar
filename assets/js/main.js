document.addEventListener("DOMContentLoaded", function () {
  const slider = document.getElementById("slider");
  const slides = slider.children;
  const totalSlides = slides.length;
  let index = 0;

  function showSlide(i) {
    slider.style.transform = `translateX(-${i * 100}%)`;
  }

  document.getElementById("next").addEventListener("click", () => {
    index = (index + 1) % totalSlides;
    showSlide(index);
  });

  document.getElementById("prev").addEventListener("click", () => {
    index = (index - 1 + totalSlides) % totalSlides;
    showSlide(index);
  });

  setInterval(() => {
    index = (index + 1) % totalSlides;
    showSlide(index);
  }, 4000);
});
