const waitForElements = () => {
    const courseIndexButtons = document.querySelectorAll('.courseindex-section');

    if (courseIndexButtons.length > 0) {
        // Add listeners.
        courseIndexButtons.forEach(function (button) {
            button.addEventListener('click', handleButtonClick);
        });
    } else {
        // Elements not loaded yet.
        setTimeout(waitForElements, 250);
    }
};

const handleButtonClick = function (event) {
    event.preventDefault();

    // Extract the section id from the URL.
    const url = this.href;
    const sectionNumber = url.match(/#section-(\d+)/)[1];

    // Expand relevant section if needed.
    const courseContentCollapse = document.getElementById(`coursecontentcollapse${sectionNumber}`);
    const collapseSection = document.getElementById(`collapssesection${sectionNumber}`);

    if (courseContentCollapse && !courseContentCollapse.classList.contains('show')) {
        courseContentCollapse.classList.add('show');
    }

    if (collapseSection && collapseSection.classList.contains('collapsed')) {
        collapseSection.classList.remove('collapsed');
    }

    // Go to section.
    const courseSection = document.getElementById(`section-${sectionNumber}`);
    courseSection.scrollIntoView({ behavior: 'smooth' });
};

// Only want to execute on topics view.
const expandTopics = () => {
    if (document.getElementById('page-course-view-topics')) {
        waitForElements();
    } else {
        console.log('#page-course-view-topics is not in the body');
    }
};

export const init = () => {
    expandTopics();
};