// Use only the real event posters injected by includes/hero.php (window.heroEventImages).
const images = Array.isArray(window.heroEventImages) ? window.heroEventImages : [];

let currentIndex = 0;

// identify the 4 boxes in the grid
const boxTL = document.getElementById('heroBox1'); // Top Left
const boxTR = document.getElementById('heroBox2'); // Top Right
const boxBL = document.getElementById('heroBox3'); // Bottom Left
const boxBR = document.getElementById('heroBox4'); // Bottom Right

function loadInitialImages() {
    if (!boxTL || !boxTR || !boxBL || !boxBR) return;

    const totalImages = images.length;
    if (totalImages === 0) return;

    boxTL.style.backgroundImage = `url('${images[0 % totalImages]}')`; // Top Left
    boxTR.style.backgroundImage = `url('${images[1 % totalImages]}')`; // Top Right
    boxBR.style.backgroundImage = `url('${images[2 % totalImages]}')`; // Bottom Right
    boxBL.style.backgroundImage = `url('${images[3 % totalImages]}')`; // Bottom Left
}

function rotateClockwise() {
    if(!boxTL || !boxTR || !boxBL || !boxBR) return;
    if (images.length === 0) return;

    // 1. Rotate the boxes in a clockwise direction (add slide classes to trigger CSS animations)
    boxTL.classList.add('slide-right');
    boxTR.classList.add('slide-down');
    boxBR.classList.add('slide-left');
    boxBL.classList.add('slide-up');

    setTimeout(() => {
        // 2. Update the current index to point to the next set of images (move backwards in the array)
        currentIndex = (currentIndex - 1 + images.length) % images.length;

        // 3. Load the new images into the boxes    
        boxTL.style.backgroundImage = `url('${images[currentIndex]}')`;
        boxTR.style.backgroundImage = `url('${images[(currentIndex + 1) % images.length]}')`;
        boxBR.style.backgroundImage = `url('${images[(currentIndex + 2) % images.length]}')`;
        boxBL.style.backgroundImage = `url('${images[(currentIndex + 3) % images.length]}')`;

        // 4. Remove the slide classes to reset the animation state for the next rotation
        boxTL.classList.remove('slide-right');
        boxTR.classList.remove('slide-down');
        boxBR.classList.remove('slide-left');
        boxBL.classList.remove('slide-up');

    }, 500); // match the duration of the CSS transition (0.5s) to ensure images update after the animation completes
}

// rotate images every 3 seconds
setInterval(rotateClockwise, 3000);

// load initial images when the page loads
document.addEventListener('DOMContentLoaded', loadInitialImages);