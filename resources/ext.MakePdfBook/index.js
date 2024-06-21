var isDraft = false;
var ruleBooks = [
  {
    namespace: "Main",
    label: "Main Page",
    webVersion: "/index.php/Main_Page",
  },
  {
    namespace: "Armored_Combat",
    label: "Armored Combat",
    webVersion: "/index.php/Armored_Combat:Handbook",
    pdfVersion:
      "/index.php/Special:MakePdfBook?category=Armored_Combat_Handbook",
    chapters: [
      ["Introduction", "/index.php/Armored_Combat:Introduction"],
      ["Change Log", "/index.php/Armored_Combat:Change_Log"],
      [
        "1. Combat Authorization Requirements",
        "/index.php/Armored_Combat:Combat_Authorization_Requirements",
      ],
      ["2. Rules of the Lists", "/index.php/Armored_Combat:Rules_Of_The_Lists"],
      [
        "3. Conventions of Combat",
        "/index.php/Armored_Combat:Conventions_Of_Combat",
      ],
      [
        "4. The Use of Weapons and Shields",
        "/index.php/Armored_Combat:Use_Of_Weapons_And_Shields",
      ],
      [
        "5. Acknowledgement of Blows",
        "/index.php/Armored_Combat:Acknowledgement_Of_Blows",
      ],
      [
        "6. Armour Requirements",
        "/index.php/Armored_Combat:Armor_Requirements",
      ],
      ["7. Weapon Standards", "/index.php/Armored_Combat:Weapons_Standards"],
      ["8. Siege Combat", "/index.php/Armored_Combat:Siege_Combat"],
      [
        "9. Authorization of Marshals",
        "/index.php/Armored_Combat:Procedures_For_The_Authorization_Of_Marshals",
      ],
      [
        "10. Marshaling Wars",
        "/index.php/Armored_Combat:Procedures_For_Marshaling_Wars",
      ],
      [
        "11. Marshaling Requirements",
        "/index.php/Armored_Combat:Marshaling_Requirements",
      ],
      [
        "12. Combat Injury Procedures",
        "/index.php/Armored_Combat:Combat_Injury_Procedures",
      ],
      [
        "13. Marshaling on the Field",
        "/index.php/Armored_Combat:Guidelines_For_Marshaling_On_The_Field",
      ],
      [
        "14. Combat Authorization Procedures",
        "/index.php/Armored_Combat:Combat_Authorization_Procedures",
      ],
      [
        "15. Equipment Inspection Guidelines",
        "/index.php/Armored_Combat:Equipment_Inspection_Guidelines",
      ],
      [
        "16. Experimental Weapons and Materials",
        "/index.php/Armored_Combat:Experimental_Weapons_And_Materials_Procedures",
      ],
      [
        "17. Marshal Responsibilities, Chain of Command, and Reporting",
        "/index.php/Armored_Combat:Marshal_Responsibilities_Chain_Of_Command_And_Reporting",
      ],
      [
        "18. Grievances and Sanctions",
        "/index.php/Armored_Combat:Procedures_For_Grievances_And_Sanctions",
      ],
      ["Glossary", "/index.php/Armored_Combat:Glossary"],
    ],
  },
  {
    namespace: "Siege",
    label: "Siege",
    webVersion: "/index.php/Siege:Handbook",
    pdfVersion: "/index.php/Special:MakePdfBook?category=Siege_Handbook",
    chapters: [
      ["Introduction", "/index.php/Siege:Introduction"],
      ["Change Log", "/index.php/Siege:Change_Log"],
      [
        "1. Marshaling and Authorization",
        "/index.php/Siege:Marshaling_And_Authorization",
      ],
      [
        "2. Siege Engines and Structures",
        "/index.php/Siege:Siege_Engines_And_Structures",
      ],
      ["3. Siege Ammunition", "/index.php/Siege:Siege_Ammunition"],
      [
        "4. Engine and Structure Inspection",
        "/index.php/Siege:Engine_And_Structure_Inspection",
      ],
      ["5. Siege Engine Operation", "/index.php/Siege:Siege_Engine_Operation"],
      [
        "6. Siege Ammunition Damage",
        "/index.php/Siege:Siege_Ammunition_Damage",
      ],
      [
        "7. Destroying Siege Engines and Structures",
        "/index.php/Siege:Destroying_Siege_Engines_And_Structures",
      ],
      [
        "8. Capturing Siege Engines and Structures",
        "/index.php/Siege:Capturing_Siege_Engines_And_Structures",
      ],
      ["9. Miscellaneous", "/index.php/Siege:Miscellaneous"],
      ["Glossary", "/index.php/Siege:Glossary"],
    ],
  },
  {
    namespace: "Fencing",
    label: "Fencing",
    webVersion: "/index.php/Fencing:Handbook",
    pdfVersion:
      "/index.php/Special:MakePdfBook?category=Fencing_Marshals_Handbook",
    chapters: [
      ["Change Log", "/index.php/Fencing:Change_Log"],
      ["1. Introduction", "/index.php/Fencing:Introduction"],
      ["2. General Information", "/index.php/Fencing:General_Information"],
      ["3. Conventions", "/index.php/Fencing:Conventions"],
      ["4. Categories of Fencing", "/index.php/Fencing:Categories_Of_Fencing"],
      [
        "5. Types of Fencing Combat",
        "/index.php/Fencing:Types_Of_Fencing_Combat",
      ],
      [
        "6. Descriptions of Weapons and Defensive Objects",
        "/index.php/Fencing:Descriptions_Of_Weapons_And_Defensive_Objects",
      ],
      [
        "7. Use of Weapons and Defensive Objects",
        "/index.php/Fencing:Use_Of_Weapons_And_Defensive_Objects",
      ],
      [
        "8. Acknowledgement of Blows",
        "/index.php/Fencing:Acknowledgement_Of_Blows",
      ],
      ["9. Armor Requirements", "/index.php/Fencing:Armor_Requirements"],
      ["10. Marshaling", "/index.php/Fencing:Marshaling"],
      ["11. Adverse Events", "/index.php/Fencing:Adverse_Events"],
      [
        "12. Use of Weapons, Styles, and Armor Requirements Outside of These Rules",
        "/index.php/Fencing:Use_Of_Weapons_Styles_And_Armor_Requirements_Outside_Of_These_Rules",
      ],
      ["A1. Glossary", "/index.php/Fencing:Glossary"],
      [
        "A2. Inspecting a Combatant's Arms and Armor",
        "/index.php/Fencing:Inspecting_A_Combatants_Arms_And_Armor",
      ],
      [
        "A3. Testing Methods for Penetration Resistant Armor",
        "/index.php/Fencing:Testing_Methods_For_Penetration_Resistant_Armor",
      ],
      ["A4. Marshaling Fencing", "/index.php/Fencing:Marshaling_Fencing"],
      ["A5. Authorization", "/index.php/Fencing:Authorization"],
      [
        "A6. Procedure for Experimental Programs",
        "/index.php/Fencing:Procedure_For_Experimental_Programs",
      ],
      [
        "A7. Adverse Events Reports",
        "/index.php/Fencing:Adverse_Events_Reports",
      ],
    ],
  },
  {
    namespace: "Archery",
    label: "Target Archery",
    webVersion: "/index.php/Archery:Handbook",
    pdfVersion: "/index.php/Special:MakePdfBook?category=Archery_Handbook",
    chapters: [
      ["Introduction", "/index.php/Archery:Introduction"],
      ["Change Log", "/index.php/Archery:Change_Log"],
      [
        "1. Target Archery Marshals",
        "/index.php/Archery:Target_Archery_Marshals",
      ],
      ["2. Equipment Standards", "/index.php/Archery:Equipment_Standards"],
      ["3. Range Safety", "/index.php/Archery:Range_Safety"],
      ["4. Range Courtesy", "/index.php/Archery:Range_Courtesy"],
      [
        "5. Period Style Equipment",
        "/index.php/Archery:Guidelines_For_Period_Style_Equipment",
      ],
    ],
  },
  {
    namespace: "Thrown_Weapons",
    label: "Thrown Weapons",
    webVersion: "/index.php/Thrown_Weapons:Handbook",
    pdfVersion:
      "/index.php/Special:MakePdfBook?category=Thrown_Weapons_Handbook",
    chapters: [
      ["Introduction", "/index.php/Thrown_Weapons:Introduction"],
      ["Change Log", "/index.php/Thrown_Weapons:Change_Log"],
      [
        "1. Thrown Weapons Marshals",
        "/index.php/Thrown_Weapons:Thrown_Weapons_Marshals",
      ],
      [
        "2. Equipment Standards",
        "/index.php/Thrown_Weapons:Equipment_Standards",
      ],
      ["3. Range Safety", "/index.php/Thrown_Weapons:Range_Safety"],
    ],
  },
  {
    namespace: "Equestrian",
    label: "Equestrian",
    webVersion: "/index.php/Equestrian:Handbook",
    pdfVersion: "/index.php/Special:MakePdfBook?category=Equestrian_Handbook",
    chapters: [
      ["Introduction", "/index.php/Equestrian:Introduction"],
      ["Change Log", "/index.php/Equestrian:Change_Log"],
      [
        "1. Equestrian Program and Marshals",
        "/index.php/Equestrian:Equestrian_Program_And_Marshals",
      ],
      [
        "2. Rider/Driver Requirements",
        "/index.php/Equestrian:Rider_Driver_Requirements",
      ],
      ["3. Event Requirements", "/index.php/Equestrian:Event_Requirements"],
      ["4. Equipment Standards", "/index.php/Equestrian:Equipment_Standards"],
      [
        "5. Insurance Instructions",
        "/index.php/Equestrian:Insurance_Instructions",
      ],
      [
        "6. Experimental Weapons and Activities Procedures",
        "/index.php/Equestrian:Experimental_Weapons_And_Activities_Procedures",
      ],
      [
        "7. Grievances and Sanctions Procedures",
        "/index.php/Equestrian:Grievances_And_Sanctions_Procedures",
      ],
    ],
  },
  {
    namespace: "Youth_Martial",
    label: "Youth Martial",
    webVersion: "/index.php/Youth_Martial:Handbook",
    pdfVersion:
      "/index.php/Special:MakePdfBook?category=Youth_Martial_Handbook",
    chapters: [
      ["Introduction", "/index.php/Youth_Martial:Introduction"],
      ["Change Log", "/index.php/Youth_Martial:ChangeLog"],
      ["Parent Section", "/index.php/Youth_Martial:Parent_Section"],
      [
        "Armored Combat - General Information",
        "/index.php/Youth_Martial:Armored_Combat_General_Information",
      ],
      [
        "Armored Combat - Rules of the Lists and Conventions of Combat",
        "/index.php/Youth_Martial:Armored_Combat_Rules_Of_The_Lists",
      ],
      [
        "Armored Combat - The Use of Weapons and Shields",
        "/index.php/Youth_Martial:Armored_Combat_Use_Of_Weapons_And_Shields",
      ],
      [
        "Armored Combat - Acknowledgement of Blows",
        "/index.php/Youth_Martial:Armored_Combat_Acknowledgement_Of_Blows",
      ],
      [
        "Armored Combat - Armor Requirements",
        "/index.php/Youth_Martial:Armored_Combat_Armor_Requirements",
      ],
      [
        "Armored Combat - Weapons Standards",
        "/index.php/Youth_Martial:Armored_Combat_Weapons_Standards",
      ],
      [
        "Rapier - General Information",
        "/index.php/Youth_Martial:Rapier_General_Information",
      ],
      ["Rapier - Conventions", "/index.php/Youth_Martial:Rapier_Conventions"],
      [
        "Rapier - Weapons and Parrying Devices",
        "/index.php/Youth_Martial:Rapier_Weapons_And_Parrying_Devices",
      ],
      [
        "Rapier - Protective Gear",
        "/index.php/Youth_Martial:Rapier_Protective_Gear",
      ],
      ["Organization", "/index.php/Youth_Martial:Organization"],
      [
        "Authorization of Marshals",
        "/index.php/Youth_Martial:Authorization_Of_Marshals",
      ],
      [
        "General Requirements and Restrictions for Youth Marshals",
        "/index.php/Youth_Martial:General_Requirements_And_Restrictions_For_Youth_Marshals",
      ],
      [
        "Marshaling Concerns in Rapier Combat",
        "/index.php/Youth_Martial:Marshaling_Concerns_In_Rapier_Combat",
      ],
      [
        "Adult Training of Youth",
        "/index.php/Youth_Martial:Adult_Training_Of_Youth",
      ],
      ["Injury Procedures", "/index.php/Youth_Martial:Injury_Procedures"],
      [
        "Youth Combat Authorizations",
        "/index.php/Youth_Martial:Youth_Combat_Authorizations",
      ],
      [
        "Marshal Responsibilities, Chain of Command, and Reporting",
        "/index.php/Youth_Martial:Marshal_Responsibilities_Chain_Of_Command_And_Reporting",
      ],
      ["Experimentation", "/index.php/Youth_Martial:Experimentation"],
      ["Disciplinary Actions", "/index.php/Youth_Martial:Disciplinary_Actions"],
      ["Glossary", "/index.php/Youth_Martial:Glossary"],
    ],
  },
  {
    namespace: "Armored_Steel_Combat",
    label: "Armored Steel Combat",
    webVersion: "/index.php/Armored_Steel_Combat:Handbook",
    chapters: [],
  },
];

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

var defaultBanner = "";
var defaultLogo = "/images/0/0d/Armored_combat_badge.svg";

function setBannerImage(nameSpace) {
  var namespaceObj = ruleBooks.find(function (obj) {
    return "namespace" in obj && obj.namespace === nameSpace;
  });

  var banner;
  var logo;

  if (namespaceObj === undefined) {
    banner = defaultBanner;
    logo = defaultLogo;
  } else {
    banner = namespaceObj.banner ? namespaceObj.banner : defaultBanner;
    logo = namespaceObj.logo ? namespaceObj.logo : defaultLogo;
  }

  document.getElementById("mw-head-base").style.backgroundImage =
    'url("' + banner + '")';
  document.getElementById("mw-head-base").style.backgroundRepeat = "no-repeat";
  document.getElementsByClassName("mw-wiki-logo")[0].style.backgroundImage =
    'url("' + logo + '")';
  // document.getElementsByClassName("mw-wiki-logo")[0].style.backgroundRepeat = "no-repeat";
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
  var isLoggedIn;

  try {
    isLoggedIn = mw.config.get("wgUserId");

    if (isLoggedIn === null) {
      isLoggedIn = false;
    } else {
      isLoggedIn = true;
    }
  } catch (ReferenceError) {
    isLoggedIn = false;
  }

  if (isLoggedIn) {
    showToolBox();
    showHistory();
  } else {
    hideToolBox();
    hideHistory();
  }
  var nameSpace = mw.config.get("wgCanonicalNamespace");

  setBannerImage(nameSpace);
  buildSideMenu();
}

jQuery(CustomizeModificationsOfSidebar);
