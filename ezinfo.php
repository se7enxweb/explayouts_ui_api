<?php
//
// explayouts_ui_api - JSON HTTP API and SPA shell for the Exponential Layouts admin UI, served under /explayouts_ui_api/app.
// Copyright (C) 1998 - 2026 7x. All rights reserved.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//

class explayouts_ui_apiInfo
{
    public static function info()
    {
        return array( Name => "explayouts_ui_api",
                      Version => "1.0.0",
                      Copyright => "Copyright (C) 1998 - 2026 7x. All rights reserved.",
                      License => "GNU General Public License v2.0 (or any later version)",
                      info_url => "https://github.com/se7enxweb/explayouts_ui_api" );
    }
}
