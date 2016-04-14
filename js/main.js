function toggleMore(target) {
    return target.classList.toggle("is-open");
}

function toggleCheckboxes(type, list) {
    inputs = list.querySelectorAll('input');
    Array.prototype.map.call(inputs, function(input) {
        input.checked = type === 'all';
    });
}

function addMoreLink(list) {
    var el = document.createElement("a");
    el.href = "#";
    el.className = "trigger-more pref-more";
    el.innerText = "+ More";

    el.addEventListener("click", function(e) {
        e.preventDefault();
        toggleMore(e.target.parentElement);
    });
    list.appendChild(el);
    list.classList.add("has-more");
}

function addMultiSelects(label, list) {
    var container = document.createElement("div");
    container.className = "pref-multiselect";

    ["all", "none"].map(function(type) {
        var el = document.createElement("a");
        el.href = "#";
        el.innerText = type;

        el.addEventListener("click", function(e) {
            e.preventDefault();
            toggleCheckboxes(type, list);
        });

        container.appendChild(el);
    });
    label.appendChild(container);
}

function addCheckedCount(label, list) {
    var el = document.createElement("div");
        el.className = "pref-count";

    updateCheckedCount(el, list);
    label.appendChild(el);
}

function updateCheckedCount(countElement, list) {
    var count = list.querySelectorAll('input:checked').length;
    countElement.innerText = count + " selected";
}

function hideAllSections(prefSection) {
    Array.prototype.map.call(prefSection, function(section) {
        section.classList.remove("pref-section--active");
        section.classList.add("pref-section--hidden");
    });
}

function setActiveSection(section) {
    section.classList.add("pref-section--active");
    section.classList.remove("pref-section--hidden");
}

function toggleSection(section, prefSection, list) {
    hideAllSections(prefSection);
    setActiveSection(section);
    updateCheckedCount(section.getElementsByClassName("pref-count")[0], list);
    window.location.hash = section.getElementsByClassName("pref-label")[0].innerText.replace(/[^\w]/g, '').toLowerCase();
}

function setupTabs(section, prefSection, list) {
    section.classList.add("pref-section--hidden");
    section.addEventListener("click", function() {
        toggleSection(section, prefSection, list);
    });
}

(function () {
    document.documentElement.className = "js";
    var hash = window.location.hash.replace("#","");
    var prefSection = document.getElementsByClassName("pref-section");
    Array.prototype.map.call(prefSection, function(section) {
        var isTabs = section.parentElement.classList.contains("pref-container-tabs");
        var label = section.getElementsByClassName("pref-label--container")[0];
        var list = section.getElementsByClassName("pref-list--container")[0];
        if (label) {
            addMultiSelects(label, list);
            addCheckedCount(label, list);
        }
        if (list.firstElementChild.childElementCount > 8 && !isTabs) {
            addMoreLink(list);
        }
        if (isTabs) {
            setupTabs(section, prefSection, list);
        }
    });

    if (hash) {
        setActiveSection(document.getElementById(hash));
    }
})();
