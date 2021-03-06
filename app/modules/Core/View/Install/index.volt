{#
  +------------------------------------------------------------------------+
  | PhalconEye CMS                                                         |
  +------------------------------------------------------------------------+
  | Copyright (c) 2013-2014 PhalconEye Team (http://phalconeye.com/)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconeye.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Author: Ivan Vorontsov <ivan.vorontsov@phalconeye.com>                 |
  +------------------------------------------------------------------------+
#}

{% extends "Install/layout.volt" %}

{% block title %}
    {{ 'Installation | Database'|i18n }}
{% endblock %}

{% block header %}
    {{ partial('/Install/header') }}
{% endblock %}

{% block content %}
    {% set action = 'index' %}
    {{ partial('/Install/steps') }}

    <div>
        <table>
            <thead>
            <tr>
                <th>
                    {{ 'Requirement'|i18n }}
                </th>
                <th>
                    {{ 'Required Version / Installed Version'|i18n }}
                </th>
                <th>
                    {{ 'Passed'|i18n }}
                </th>
            </tr>
            </thead>
            <tbody>
            {% for req in reqs %}
                <tr>
                    <td class="table-column-name">
                        {{ req['name'] }}
                    </td>
                    <td>
                        {{ req['version'] }} / {{ req['installed_version'] }}
                    </td>
                    <td>
                        {% if req['passed'] %}
                            <img alt="Passed" src="{{ url('assets/img/core/install/good.png') }}"/>
                        {% else %}
                            <img alt="Not Passed" src="{{ url('assets/img/core/install/bad.png') }}"/>
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}
            </tbody>
        </table>

        <table>
            <thead>
            <tr>
                <th class="table-column-left">
                    {{ 'Path'|i18n }}
                </th>
                <th>
                    {{ 'Writable'|i18n }}
                </th>
            </tr>
            </thead>
            <tbody>
            {% for path in pathInfo %}
                <tr>
                    <td class="table-column-name table-column-left">
                        {{ path['name'] }}
                    </td>
                    <td>
                        {% if path['is_writable'] %}
                            <img alt="Passed" src="{{ url('assets/img/core/install/good.png') }}"/>
                        {% else %}
                            <img alt="Not Passed" src="{{ url('assets/img/core/install/bad.png') }}"/>
                        {% endif %}
                    </td>
                </tr>
            {% endfor %}
            </tbody>
        </table>

        {% if passed %}
            <a href="{{ url('install/database') }} " class="proceed">{{ 'Install'|i18n }}</a>
        {% else %}
            <div class="error">{{ 'Please, install all requirements and check pathes.'|i18n }}</div>
        {% endif %}
    </div>
{% endblock %}