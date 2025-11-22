import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import issueService from '../services/issueService';
import Card from '../components/Card';
import Button from '../components/Button';
import Loading from '../components/Loading';
import StatusBadge from '../components/StatusBadge';
import { COLORS } from '../constants/colors';

const IssuesScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [issues, setIssues] = useState([]);

  useEffect(() => {
    loadIssues();
  }, []);

  const loadIssues = async () => {
    try {
      const data = await issueService.getIssues();
      setIssues(data.issues || data);
    } catch (error) {
      console.error('Error loading issues:', error);
      Alert.alert('Erreur', 'Impossible de charger les signalements');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadIssues();
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'urgent':
        return COLORS.error;
      case 'high':
        return COLORS.warning;
      case 'medium':
        return COLORS.info;
      case 'low':
        return COLORS.gray[500];
      default:
        return COLORS.gray[500];
    }
  };

  const renderIssue = ({ item }) => (
    <TouchableOpacity
      onPress={() => navigation.navigate('IssueDetails', { issueId: item.id })}
    >
      <Card>
        <View style={styles.issueHeader}>
          <Text style={styles.issueNumber}>{item.issue_number}</Text>
          <StatusBadge status={item.status} label={item.status_label} />
        </View>

        <Text style={styles.subject}>{item.subject}</Text>

        <View style={styles.issueFooter}>
          <View style={styles.typeContainer}>
            <Text style={styles.label}>Type</Text>
            <Text style={styles.value}>{item.type_label}</Text>
          </View>

          <View style={styles.priorityContainer}>
            <Text style={styles.label}>Priorité</Text>
            <Text style={[styles.priority, { color: getPriorityColor(item.priority) }]}>
              {item.priority_label}
            </Text>
          </View>

          <View>
            <Text style={styles.label}>Créé le</Text>
            <Text style={styles.value}>
              {new Date(item.created_at).toLocaleDateString('fr-FR')}
            </Text>
          </View>
        </View>
      </Card>
    </TouchableOpacity>
  );

  if (loading) {
    return <Loading />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Button
          title="+ Nouveau signalement"
          onPress={() => navigation.navigate('CreateIssue')}
          size="medium"
        />
      </View>

      <FlatList
        data={issues}
        renderItem={renderIssue}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={
          <Card>
            <Text style={styles.emptyText}>Aucun signalement</Text>
          </Card>
        }
      />
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  header: {
    padding: 16,
    paddingBottom: 8,
  },
  listContent: {
    padding: 16,
    paddingTop: 8,
  },
  issueHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  issueNumber: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text.secondary,
  },
  subject: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 12,
  },
  issueFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: COLORS.divider,
    paddingTop: 12,
  },
  typeContainer: {
    flex: 1,
  },
  priorityContainer: {
    flex: 1,
  },
  label: {
    fontSize: 12,
    color: COLORS.text.secondary,
    marginBottom: 4,
  },
  value: {
    fontSize: 14,
    color: COLORS.text.primary,
  },
  priority: {
    fontSize: 14,
    fontWeight: '600',
  },
  emptyText: {
    textAlign: 'center',
    color: COLORS.text.secondary,
    fontSize: 14,
  },
});

export default IssuesScreen;
