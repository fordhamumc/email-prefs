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

(function () {
    document.documentElement.className = "js";
    var prefSection = document.getElementsByClassName("pref-section");
    Array.prototype.map.call(prefSection, function(section) {
        var label = section.getElementsByClassName("pref-label--container")[0];
        var list = section.getElementsByClassName("pref-list--container")[0];
        if (label) {
            addMultiSelects(label, list);
        }
        if (list.firstElementChild.childElementCount > 8) {
            addMoreLink(list);
        }
    });
})();
