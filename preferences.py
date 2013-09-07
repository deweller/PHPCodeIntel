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
        ("rescan_on_save", False),
        ("php_extensions", (".php")),
        ("debug_enabled", False),
        ("scan_all_project_folders", True),
        ("include_dirs", []),
        ("exclude_patterns", []),
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

        return scan_dirs



    def getDBFilePath(self, view):
        project_path = view.window().project_file_name()

        if project_path == None:
            return None
        
        basedir,basename = os.path.split(project_path)
        name = basename[:-16]+'-'+hashlib.md5(project_path.encode('utf-8')).hexdigest()

        return os.path.join(self.db_folder, name+'.sqlite3')



def load(view):
    prefs = PCIPrefs()
    prefs.load(view)
    return prefs
