var isDraft = false;

function hideToolBox() {
  document.getElementById("p-tb").remove();
}
function showToolBox() {
  document.getElementById("p-tb").style.visibility = "visible";
}
function hideHistory() {
  document.getElementById("ca-history").remove();
}
function showHistory() {
  document.getElementById("ca-history").style.visibility = "visible";
}

function setBannerImage() {
  if (makepdfbookBanner) {
    let mwHeadBaseStyle = document.getElementById("mw-head-base").style;

    mwHeadBaseStyle.backgroundImage = 'url("' + makepdfbookBanner + '")';
    mwHeadBaseStyle.backgroundRepeat = "no-repeat";
  }
}

function buildSideMenu() {
  currentNamespace = mw.config.get("wgCanonicalNamespace");
  var navigationMenu = document.getElementById("mw-panel");

  for (var ruleBook in ruleBooks) {
    var bookObj = ruleBooks[ruleBook];

    var bookDiv = document.createElement("div");
    bookDiv.setAttribute("class", "portal");
    navigationMenu.insertBefore(bookDiv, document.getElementById("p-tb"));

    var title = document.createElement("h3");
    //title.setAttribute("class", "portal");
    //title.setAttribute("role", "navigation");
    if ("webVersion" in bookObj) {
      var titleLink = document.createElement("a");
      titleLink.setAttribute("href", bookObj.webVersion);
      titleLink.appendChild(document.createTextNode(bookObj.label));
      title.appendChild(titleLink);
    } else {
      title.appendChild(document.createTextNode(bookObj.label));
    }
    bookDiv.appendChild(title);

    var navBody = document.createElement("div");
    navBody.setAttribute("class", "body");
    bookDiv.appendChild(navBody);

    var entries = document.createElement("ul");
    navBody.appendChild(entries);

    if (
      bookObj.namespace === mw.config.get("wgCanonicalNamespace") &&
      "chapters" in bookObj
    ) {
      var chapterCount = bookObj.chapters.length;
      for (var i = 0; i < chapterCount; i++) {
        chapter = bookObj.chapters[i];
        var chapterLink = document.createElement("a");
        chapterLink.appendChild(document.createTextNode(chapter[0]));
        chapterLink.setAttribute("href", chapter[1]);
        var li = document.createElement("li");
        li.appendChild(chapterLink);
        entries.appendChild(li);
      }
    }

    if ("pdfVersion" in bookObj) {
      var pdfLink = document.createElement("a");
      pdfLink.appendChild(document.createTextNode("PDF version"));
      pdfLink.setAttribute("href", bookObj.pdfVersion);
      var pdfHolder = document.createElement("span");
      pdfHolder.appendChild(pdfLink);
      bookDiv.appendChild(pdfHolder);
    }
  }
}

function CustomizeModificationsOfSidebar() {
  // var nameSpace = mw.config.get("wgCanonicalNamespace");
  // setBannerImage(nameSpace);
}

jQuery(CustomizeModificationsOfSidebar);
