const handleButtonClick = function (event) {
    event.preventDefault();

    const button = event.target.closest('.courseindex-section');

    if (button) {
        const url = button.href;
        const sectionMatch = url.match(/#section-(\d+)/);
        let sectionNumber = null;

        // Using hashbang so expand current page topic.
        if (sectionMatch) {
            sectionNumber = sectionMatch[1];
            const courseSection = document.getElementById(`section-${sectionNumber}`);

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
            courseSection.scrollIntoView({behavior: 'smooth'});
        } else {
            // Go to the external section.
            window.location.href = url;
        }
    }
};

const courseIndexEvent = () => {
    const courseIndex = document.getElementById('courseindex-content');

    if (courseIndex) {
        courseIndex.addEventListener('click', handleButtonClick);
    }
};

// Only want to execute on topics view.
const expandTopics = () => {
    if (document.getElementById('page-course-view-topics') && !document.querySelectorAll('.section-summary').length > 0) {
        courseIndexEvent();
    }
};

export const init = () => {
    expandTopics();
};