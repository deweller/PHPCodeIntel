import sublime
import os
import json
import re
import hashlib


st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
    st_version = 3

class PCIPrefs:

    preference_options = (
        ("daemon_port", 20001),
        ("php_path", "/usr/bin/php"),
        ("autocomplete_enabled", False),
        ("rescan_on_save", False),
        ("php_extensions", (".php")),
        ("debug_enabled", False),
        ("scan_all_project_folders", True),
        ("include_dirs", []),
    )

    def load(self, view):
        self.settings = sublime.load_settings('PHPCodeIntel.sublime-settings')

        if sublime.active_window() is not None:
            project_settings = view.settings()
            if project_settings.has("PHPCodeIntel"):
                self.project_settings = project_settings.get('PHPCodeIntel')
            else:
                self.project_settings = {}
        else:
            self.project_settings = {}

        for (key, default) in self.preference_options:
            setattr(self, key, self._load_setting(key, default))

        self._init_db_path()

    def _load_setting(self, key, default=None):
        if key in self.project_settings:
            return self.project_settings.get(key)

        value = self.settings.get(key)
        if value == None:
            value = default

        return value

    def _init_db_path(self):
        self.db_folder = os.path.expanduser(os.path.join('~', '.phpcodeintel'))

        if not os.path.exists(self.db_folder):
            os.makedirs(self.db_folder)

    def getProjectScanDirs(self, view):
        scan_dirs = []

        if self.scan_all_project_folders:
            folders = view.window().folders()
            scan_dirs.extend(folders)

        scan_dirs.extend(self.include_dirs)

        print "scan_all_project_folders: "+str(self.scan_all_project_folders)
        print "scan_dirs: "+str(scan_dirs)

        return scan_dirs



    def getDBFilePath(self, view):
        project_path = get_project(view)

        if project_path == None:
            return None
        
        basedir,basename = os.path.split(project_path)
        name = basename[:-16]+'-'+hashlib.md5(project_path).hexdigest()

        return os.path.join(self.db_folder, name+'.sqlite3')


##############################################################################################################################
# get project

def get_project(view):
    if st_version == 3:
        return get_project_st3(view)
    else:
        return get_project_st2(view)

def get_project_st3(view):
    # I think ST3 has a better way of doing this...
    #    implement that here

    return get_project_st2(view)



# get_project_st2 function thanks to Isaac Muse
# Copyright (c) 2012 Isaac Muse <isaacmuse@gmail.com>
def get_project_st2(view):
    win_id = view.window().id()

    project = None
    reg_session = os.path.join(sublime.packages_path(), "..", "Settings", "Session.sublime_session")
    auto_save = os.path.join(sublime.packages_path(), "..", "Settings", "Auto Save Session.sublime_session")
    session = auto_save if os.path.exists(auto_save) else reg_session

    if not os.path.exists(session) or win_id == None:
        return project

    try:
        with open(session, 'r') as f:
            # Tabs in strings messes things up for some reason
            j = json.JSONDecoder(strict=False).decode(f.read())
            for w in j['windows']:
                if w['window_id'] == win_id:
                    if "workspace_name" in w:
                        if sublime.platform() == "windows":
                            # Account for windows specific formatting
                            project = normpath(w["workspace_name"].lstrip("/").replace("/", ":/", 1))
                        else:
                            project = w["workspace_name"]
                        break
    except:
        pass

    # Throw out empty project names
    if project == None or re.match(".*\\.sublime-project", project) == None or not os.path.exists(project):
        project = None

    return project


def load(view):
    prefs = PCIPrefs()
    prefs.load(view)
    return prefs
