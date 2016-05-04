function toggleCheckboxes(type, list) {
    inputs = list.querySelectorAll('input');
    Array.prototype.map.call(inputs, function(input) {
        input.checked = type === 'all';
    });
}

function addMultiSelects(list) {
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
    list.insertBefore(container, list.firstChild);
}

function createOptionsContainer(list, label) {
    var header = document.createElement("h3");
        header.className = "pref-title";
        header.innerText = label.innerText;

    var closeButton = document.createElement("button");
        closeButton.innerText = "Close";
        closeButton.addEventListener("click", function(e) {
            e.preventDefault();
            closeOptions(list.parentElement);
        });

    var footer = document.createElement("footer");
        footer.className = "pref-footer";
        footer.appendChild(closeButton);
    list.insertBefore(header, list.firstChild);
    list.appendChild(footer);
}

function clickOff(e) {
    if (e.target.classList.contains("pref-list--toggle")) {
        closeOptions(e.target)
    }
}

function closeOptions(listContainer) {
    updateSelected(listContainer);
    listContainer.removeEventListener("click", clickOff);
    document.body.classList.remove("modal-open");
    listContainer.parentElement.classList.remove("active");
}

function openOptions(listContainer) {
    document.body.classList.add("modal-open");
    listContainer.addEventListener("click", clickOff);
    listContainer.classList.add("active");
    listContainer.querySelector(".pref-list").scrollTop = 0;
}

function linkLabel(label, prefSections) {
    var link = document.createElement("a");
        link.href = "#";
        link.innerText = label.innerText;
    link.addEventListener("click", function(e) {
        var currentSection = label.parentElement;
        e.preventDefault();
        Array.prototype.map.call(prefSections, function(section) {
            section.classList.remove("active");
        });
        openOptions(currentSection);
    });
    label.innerText = '';
    label.appendChild(link);
}

function updateSelected(listContainer) {
    var selected = listContainer.querySelectorAll("input:checked");
    var parent = listContainer.parentElement;
    var container = parent.querySelector(".pref-selected") || document.createElement("ul");
        container.className = "pref-selected";
        container.innerHTML = "";

    Array.prototype.map.call(selected, function(input, index) {
        var item = document.createElement("li");
            item.className = "pref-selected-item";
            item.innerText = input.parentElement.innerText.trim() + ((index < selected.length - 1) ? ', ' : '');
        container.appendChild(item);
    });
    parent.appendChild(container);
}

function watchTextInputChanges(textInputs) {
    function isDirty(input) {
        var parent = input.parentElement
        if(input.value) {
            parent.classList.add("dirty");
        } else {
            parent.classList.remove("dirty");
        }
    }
    Array.prototype.map.call(textInputs, function(input) {
        isDirty(input);
        var parent = input.parentElement
        input.onfocus = function () {
            parent.classList.add("focused");
        };
        input.onblur = function () {
            parent.classList.remove("focused");
        };
        input.addEventListener("keyup", function(e) {
            isDirty(e.target);
        });
    });
}

(function () {
    document.documentElement.className = "js";
    var prefSections = document.getElementsByClassName("pref-section");
    var textInputs = document.querySelectorAll(".input-text");
    Array.prototype.map.call(prefSections, function(section) {
        var label = section.getElementsByClassName("pref-label")[0];
        var list = section.getElementsByClassName("pref-list")[0];
        if (list) {
            list.parentElement.classList.add('pref-list--toggle');
            addMultiSelects(list);
            updateSelected(list.parentElement);
        }
        if (label) {
            linkLabel(label, prefSections);
        }
        if (list && label) {
            createOptionsContainer(list, label);
        }
    });
    watchTextInputChanges(textInputs);
})();
