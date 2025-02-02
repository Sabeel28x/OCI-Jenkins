pipeline {
    agent any
    triggers {
        githubPush()
    }
    environment {
        OCI_POOL_ID = 'ocid1.instancepool.oc1.ap-mumbai-1.aaaaaaaaxmkfrt26vhfatmyyumivcecin5rs2s4o5gagrd7jw3uo35oc26ta'
        OCI_COMPARTMENT_ID = 'ocid1.compartment.oc1..aaaaaaaa3s5mtrcqpxjacf53plx4cvlbw4tttytumicdcojrbe2twmmyib4q'
        REPO_URL = 'https://github.com/Sabeel28x/OCI-Jenkins.git'
        BRANCH = 'main'
        APACHE_DOC_ROOT = '/var/www/html'
        TEAMS_WEBHOOK_URL = 'https://prod-24.centralindia.logic.azure.com:443/workflows/c01ef101a97a49aaaa3df9d6446738b9/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=u-ZjwsTHFM5tP0U7I6SHvKpWP6YLhIvyjKlDMf2EUck'
    }
    
    stages {
        stage('Fetch Instance IPs') {
            steps {
                script {
                    echo "Fetching instance list from OCI Instance Pool..."
                    
                    def instanceList = sh(script: """
                        oci compute-management instance-pool list-instances \
                            --instance-pool-id ${OCI_POOL_ID} \
                            --compartment-id ${OCI_COMPARTMENT_ID} \
                            --output json
                    """, returnStdout: true).trim()

                    def instances = readJSON text: instanceList
                    def instanceIds = instances.data.collect { it.id }

                    def instanceIps = []
                    instanceIds.each { instanceId ->
                        def vnicInfo = sh(script: """
                            oci compute instance list-vnics \
                                --instance-id ${instanceId} \
                                --output json
                        """, returnStdout: true).trim()

                        def vnics = readJSON text: vnicInfo
                        def privateIp = vnics.data[0]['private-ip']
                        instanceIps.add(privateIp)
                    }

                    if (instanceIps.isEmpty()) {
                        error("No private IPs found. Cannot proceed with deployment.")
                    }

                    echo "Private IPs: ${instanceIps}"
                    env.INSTANCE_IPS = instanceIps.join(',')
                }
            }
        }

        stage('Deploy Code to Instances') {
            steps {
                script {
                    def instanceIps = env.INSTANCE_IPS.split(',')

                    if (instanceIps.length == 0 || instanceIps[0] == '') {
                        error("No valid IPs found. Cannot proceed with deployment.")
                    }

                    sshagent(['oci']) {
                        instanceIps.each { ip ->
                            echo "Deploying code to instance with IP: ${ip}"

                            sh """
                                ssh -o StrictHostKeyChecking=no opc@${ip} '
                                    cd ${APACHE_DOC_ROOT} && \
                                    sudo git clone ${REPO_URL} && \
                                    sudo mv OCI-Jenkins/index.php ${APACHE_DOC_ROOT} && \
                                    sudo rm -rf OCI-Jenkins && \
                                    sudo systemctl restart httpd
                                '
                            """
                        }
                    }
                }
            }
        }
    }

    post {
        success {
            script {
                def teamsPayload = [
                    type: "message",
                    attachments: [
                        [
                            contentType: "application/vnd.microsoft.card.adaptive",
                            content: [
                                type: "AdaptiveCard",
                                version: "1.4",
                                body: [
                                    [
                                        type: "TextBlock",
                                        text: "✅ Jenkins Build Successful",
                                        weight: "bolder",
                                        size: "large"
                                    ],
                                    [
                                        type: "TextBlock",
                                        text: "The build was successful. Check the Jenkins console output for details."
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]

                def jsonPayload = groovy.json.JsonOutput.toJson(teamsPayload)

                sh """
                    curl -X POST "${TEAMS_WEBHOOK_URL}" \
                    -H "Content-Type: application/json" \
                    -d '${jsonPayload}'
                """
            }
        }

        failure {
            script {
                def teamsPayload = [
                    type: "message",
                    attachments: [
                        [
                            contentType: "application/vnd.microsoft.card.adaptive",
                            content: [
                                type: "AdaptiveCard",
                                version: "1.4",
                                body: [
                                    [
                                        type: "TextBlock",
                                        text: "❌ Jenkins Build Failed",
                                        weight: "bolder",
                                        size: "large",
                                        color: "attention"
                                    ],
                                    [
                                        type: "TextBlock",
                                        text: "The build failed. Check the Jenkins console output for details."
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]

                def jsonPayload = groovy.json.JsonOutput.toJson(teamsPayload)

                sh """
                    curl -X POST "${TEAMS_WEBHOOK_URL}" \
                    -H "Content-Type: application/json" \
                    -d '${jsonPayload}'
                """
            }
        }
    }
}
