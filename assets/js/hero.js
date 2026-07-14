// Use only the real event posters injected by includes/hero.php (window.heroEventImages).
const images = Array.isArray(window.heroEventImages) ? window.heroEventImages : [];

let currentIndex = 0;

// identify the 4 boxes in the grid
const boxTL = document.getElementById('heroBox1'); // Top Left
const boxTR = document.getElementById('heroBox2'); // Top Right
const boxBL = document.getElementById('heroBox3'); // Bottom Left
const boxBR = document.getElementById('heroBox4'); // Bottom Right

const heroBoxes = [boxTL, boxTR, boxBR, boxBL];

function renderBoxes(startIndex = 0) {
    const totalImages = images.length;
    if (!heroBoxes.every(Boolean)) return;

    if (totalImages === 0) {
        heroBoxes.forEach((box) => {
            box.style.backgroundImage = 'none';
        });
        return;
    }

    const visibleCount = Math.min(totalImages, heroBoxes.length);

    heroBoxes.forEach((box, slotIndex) => {
        if (slotIndex < visibleCount) {
            const imageIndex = (startIndex + slotIndex) % totalImages;
            box.style.backgroundImage = `url('${images[imageIndex]}')`;
        } else {
            box.style.backgroundImage = 'none';
        }
    });
}

function loadInitialImages() {
    renderBoxes(0);
}

function rotateClockwise() {
    if(!boxTL || !boxTR || !boxBL || !boxBR) return;
    if (images.length <= 1) return;

    // 1. Rotate the boxes in a clockwise direction (add slide classes to trigger CSS animations)
    boxTL.classList.add('slide-right');
    boxTR.classList.add('slide-down');
    boxBR.classList.add('slide-left');
    boxBL.classList.add('slide-up');

    setTimeout(() => {
        // 2. Update the current index to point to the next set of images (move backwards in the array)
        currentIndex = (currentIndex - 1 + images.length) % images.length;

        // 3. Load the new images into the boxes
        renderBoxes(currentIndex);

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